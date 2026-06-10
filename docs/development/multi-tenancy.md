# Multi-Tenancy Architecture — Binding

Multi-tenancy is a **foundational premise** of NeNe Field, not a feature bolted on
later. One installation hosts one or more **organizations** (tenants); **every row
of tenanted data belongs to exactly one organization**, and cross-tenant access is
impossible by construction.

> **Status: binding.** Tenant-isolation violations are **security defects** that
> block merge to `main` — no temporary exceptions, no ADR escape hatch. This
> document adopts the proven pattern from sibling **nene-records** and refines
> [ADR 0004](../adr/0004-multi-tenancy-and-roles.md) with the concrete mechanism
> (see [ADR 0013](../adr/0013-tenant-resolution-and-isolation.md)).

**Read with:** [`backend-standards.md`](./backend-standards.md) §7,
[`../explanation/domain-model.md`](../explanation/domain-model.md),
identifiers [`../terms.md`](../terms.md). Self-review:
[`../review/backend.md`](../review/backend.md).

---

## 1. Governing principle

1. **Every tenanted row carries `organization_id` (NOT NULL).** No tenanted table,
   query, or API path is exempt.
2. **The tenant is resolved by the server, never trusted from client input.** A
   client-supplied `organization_id` in a body/query is **ignored** for scoping;
   the scope comes from the request context (§2–§3).
3. **Isolation is enforced at the repository layer**, in the `WHERE` clause of
   every statement — not only in the use case, not only in the UI.
4. **Fail closed.** If the org cannot be resolved, the request is rejected; a
   repository on a tenanted route must never run a query without an org scope.

---

## 2. Tenant resolution (pluggable strategy)

How the current organization is determined from an HTTP request is a **pluggable
strategy** (mirrors nene-records):

```php
interface OrgResolutionStrategyInterface
{
    /** Returns the org slug / custom-domain identifier, or null if undeterminable. */
    public function resolve(ServerRequestInterface $request): ?string;
}
```

| Strategy | Resolves from | Use |
| --- | --- | --- |
| `EnvResolutionStrategy` | `NENE_FIELD_ORG_SLUG` env | **Tier A** shared hosting, single org |
| `SubdomainResolutionStrategy` | `{slug}.{NENE_FIELD_BASE_DOMAIN}` | **Tier B** multi-org default |
| `PathPrefixResolutionStrategy` | `/{slug}/…` path prefix | Tier B alternative |
| `CustomDomainResolutionStrategy` | vanity domain → `organizations.custom_domain` | Tier B optional |

Selection is configured by **`NENE_FIELD_TENANT_RESOLUTION`** (`single` |
`subdomain` | `path`), with `NENE_FIELD_ORG_SLUG` / `NENE_FIELD_BASE_DOMAIN` as
inputs. Default is `single` so a fresh shared-hosting install works out of the box.

### Tier mapping (ADR 0003)

| Tier | Deployment | Resolution |
| --- | --- | --- |
| **A** | Shared hosting, SQLite, **one org** | `single` (Env) |
| **B** | Docker / VPS, MySQL, **many orgs** | `subdomain` (default) or `path` / custom domain |

---

## 3. `OrgResolverMiddleware` + request-scoped holder

A PSR-15 middleware resolves the org early and stores its id for downstream
repositories, using the framework's `Nene2\Http\RequestScopedHolder<int>`.

Resolution order:

1. If the path matches a **bypass prefix**, pass through with the org id unset.
2. `strategy->resolve(request)` → identifier; `null` → `404 org-not-resolved`.
3. `OrganizationRepository::findBySlug()` then `findByCustomDomain()`;
   none → `404 org-not-found`.
4. `!organization.is_active` → `403 org-inactive`.
5. Store `organization.id` in `RequestScopedHolder<int>`; also set request
   attributes `nene_field.org.id` and `nene_field.org.slug`.

**Bypass prefixes** (no org context needed):

```
/health
/auth/        (login/logout/me)
/organizations   (superadmin org management)
/superadmin/
```

Repositories invoked on bypass routes MUST NOT call `$orgId->get()`.

---

## 4. Repository enforcement (binding)

Every tenanted `Pdo*Repository` receives `RequestScopedHolder<int> $orgId` by
constructor injection and includes `organization_id = ?` (bound to
`$this->orgId->get()`) in **every** statement — `SELECT`, `INSERT`, `UPDATE`,
`DELETE`, and existence checks — combined with the soft-delete filter where the
table uses one.

```php
// read
$this->query->fetchOne(
    'SELECT … FROM reports WHERE report_id = ? AND organization_id = ?',
    [$reportId, $this->orgId->get()],
);

// write
$this->query->insert(
    'INSERT INTO reports (organization_id, …) VALUES (?, …)',
    [$this->orgId->get(), …],
);
```

Rules:

- The org id always comes from `$orgId->get()`, **never** from the request body,
  query string, or a DTO field. A client cannot select another tenant.
- A "not found in this org" result returns the domain's not-found path
  (e.g. `report-not-found`, 404) — never another tenant's row, never a 403 that
  leaks existence.
- The data flow is unchanged from `backend-standards.md`: Handler → UseCase →
  RepositoryInterface → `Pdo*Repository`; the org scope is applied in the adapter.

---

## 5. Org ↔ authenticated-user consistency (defense-in-depth)

Tenant resolution (request) and authentication (JWT) are independent inputs, so
they MUST be reconciled:

- The JWT carries the authenticated user's `organization_id` (ADR 0004).
- After authentication, the system MUST assert **JWT `organization_id` ==
  resolved org id** (from `RequestScopedHolder`). A mismatch is a cross-tenant
  attempt → **`403 forbidden`**.
- Only `superadmin`, on bypass/superadmin routes, operates across tenants.

Middleware order on tenanted routes:

```
OrgResolverMiddleware → BearerTokenMiddleware (auth) → CapabilityMiddleware (RBAC + org-consistency)
```

---

## 6. Schema (binding)

`organizations` (tenant root):

| Column | Type | Notes |
| --- | --- | --- |
| `organization_id` | PK | |
| `name` | string | Display name |
| `slug` | string | **unique**; resolution key |
| `custom_domain` | string / null | **unique**; optional vanity domain |
| `is_active` | boolean | Inactive → `403 org-inactive` |
| `created_at` / `updated_at` | datetime | |

(Plus existing settings: `ai_summary_enabled`, `ai_api_url`, `notification_email`,
`webhook_url` — domain-model.)

Every **tenanted** table (`reports`, `report_templates`, `report_attachments`,
`audit_events`, `users`, …) MUST have:

- `organization_id` **NOT NULL**, with an index (`idx_{table}_organization_id`);
- it in every query's `WHERE` (and in composite uniqueness, e.g.
  `uniq_users_email_org` = `(organization_id, email)` — uniqueness is **per tenant**,
  never global).

A user belongs to exactly one organization (`users.organization_id`).

---

## 7. Testing (binding)

- **Tenant-isolation tests are mandatory** for every tenanted repository/endpoint:
  a row created under org A MUST be invisible (404) and unmodifiable (404/403) when
  the resolved org is B.
- Repository fakes in use-case tests are **org-scoped** the same way (a fake that
  ignores `organization_id` is a defective test).
- Resolution tests cover: each strategy resolves correctly; unresolved →
  `org-not-resolved`; unknown slug → `org-not-found`; inactive → `org-inactive`;
  JWT/resolved-org mismatch → `403`.

---

## 8. Forbidden (blocks merge)

- A tenanted query without `organization_id` in its `WHERE` / `INSERT`.
- Taking the org id from a request body, query param, or DTO instead of
  `RequestScopedHolder`.
- Global uniqueness on a per-tenant field (e.g. unique `users.email` across all orgs).
- Returning or mutating a row from another organization; leaking another tenant's
  existence via error differences.
- A new tenanted table without `organization_id NOT NULL` + index.
- Cross-tenant access outside the explicit superadmin/bypass routes.
