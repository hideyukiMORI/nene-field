# ADR 0004: Multi-Tenancy and Roles

## Status

accepted

## Context

NeNe Field may be operated by multiple organizations on the same installation
(SaaS scenario) or by a single organization on self-hosted hardware. In both cases,
data isolation between organizations is a hard requirement.

The sibling products (Invoice, Vault, Records) use `organization_id` as the tenant
scope key on every tenanted table, with JWT-embedded `org_id` enforced by middleware.

NeNe Field needs a role model that covers the three primary personas:
- **Field worker / submitter** — submits reports; sees own reports
- **Approver / team lead** — reviews and approves reports for their team
- **Admin** — manages users, templates, org settings, exports
- **Superadmin** — cross-organization access (operator of the installation)

## Decision

**Multi-tenancy:**
- Every tenanted table carries `organization_id` (UUID).
- JWT payload includes `org_id`; `BearerTokenMiddleware` enforces isolation.
- Queries must always include `organization_id` in WHERE clauses.
- No cross-tenant data access except via `superadmin`.

**Roles (RBAC):**

| Role | Canonical value | Capabilities |
| --- | --- | --- |
| Submitter | `submitter` | Submit / edit own draft reports; view own reports |
| Approver | `approver` | All submitter capabilities + approve / reject reports in their scope |
| Admin | `admin` | All approver capabilities + user management + template management + export |
| Superadmin | `superadmin` | Cross-organization access; installation management |

Role is stored in `users.role` using the canonical values from `docs/terms.md §3`.

**Scope for approvers:**
In Phase 1, approvers can approve any report in their organization.
Team-scoped approval (approver sees only their team's reports) is deferred to Phase 2.

## Consequences

- Every handler must check both `organization_id` isolation and role authorization
- `superadmin` endpoints need a separate auth path outside the normal tenant middleware
- Adding team-scoped approval later will require a migration and handler changes

## Related

- Issue: `#1`
- See also: `docs/terms.md §2–3`
- See also: NENE2 `docs/adr/0008-jwt-authentication.md`
