# ADR 0013: Tenant Resolution & Isolation (adopt nene-records pattern)

## Status

accepted

## Context

[ADR 0004](./0004-multi-tenancy-and-roles.md) established that NeNe Field is
multi-tenant with `organization_id` embedded in the JWT and enforced on tenanted
endpoints. It did not specify **how the current organization is resolved per
request** nor **where isolation is mechanically enforced** — leaving room for
inconsistent, leak-prone implementations.

The sibling product **nene-records** already ships a proven multi-tenancy
implementation:

- a pluggable `OrgResolutionStrategyInterface` (Subdomain / CustomDomain / Path /
  Env) selected by configuration;
- an `OrgResolverMiddleware` that resolves the org, bypasses health/auth/superadmin
  routes, returns 404/403 on unresolved/unknown/inactive orgs, and stores the id in
  `Nene2\Http\RequestScopedHolder<int>`;
- repositories that read the holder and include `organization_id = ?` in **every**
  query.

NeNe Field's dual-tier model (ADR 0003) maps onto this cleanly: Tier A
(shared hosting, one org) uses the Env strategy; Tier B (Docker/VPS, many orgs)
uses subdomain/path/custom-domain.

## Decision

Adopt the nene-records pattern as NeNe Field's **binding** multi-tenancy
architecture, documented in [`../development/multi-tenancy.md`](../development/multi-tenancy.md):

1. **Request-based resolution via a pluggable strategy**, selected by
   `NENE_FIELD_TENANT_RESOLUTION` (`single` | `subdomain` | `path`), with
   `NENE_FIELD_ORG_SLUG` / `NENE_FIELD_BASE_DOMAIN` inputs. Default `single`.
2. **`OrgResolverMiddleware`** resolves the org and stores its id in
   `RequestScopedHolder<int>`; bypass prefixes (`/health`, `/auth/`,
   `/organizations`, `/superadmin/`) skip resolution; failures return
   `org-not-resolved` (404), `org-not-found` (404), `org-inactive` (403).
3. **Repository-level enforcement:** every tenanted `Pdo*Repository` includes
   `organization_id = ?` from the holder in every statement. The org id is never
   taken from client input.
4. **Org ↔ JWT consistency:** the authenticated user's `organization_id` MUST equal
   the resolved org id, else `403 forbidden`. Only `superadmin` crosses tenants, on
   bypass routes.
5. **Schema:** an `organizations` root table (`slug` unique, `custom_domain` unique/
   nullable, `is_active`); `organization_id NOT NULL` + index on every tenanted
   table; per-tenant (not global) uniqueness for fields like `users.email`.

This **refines ADR 0004** (the JWT remains the user/role/org carrier; resolution +
enforcement are now specified) and does not change the roles defined there.

## Consequences

- Tenant isolation is enforced uniformly and testably; cross-tenant access is
  impossible by construction, and isolation tests are mandatory.
- Tier A works out of the box (`single`), Tier B supports subdomain/path/custom
  domain without code changes — only configuration.
- New tenanted tables and repositories must follow the binding rules in
  `multi-tenancy.md`; violations block merge.
- A small framework dependency is taken on `Nene2\Http\RequestScopedHolder`.

## Related

- Issue: `#11`
- PR: `#000`
- Refines: ADR 0004 (multi-tenancy and roles)
- Related: ADR 0003 (dual-tier deployment), `docs/development/multi-tenancy.md`,
  `docs/development/backend-standards.md` §7; reference implementation: `nene-records`
- Supersedes: none
- Superseded by: none
