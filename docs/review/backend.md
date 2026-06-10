# Backend Self-Review

**Binding.** Run for any PHP change. Source of truth:
[`../development/backend-standards.md`](../development/backend-standards.md),
[`../development/naming-conventions.md`](../development/naming-conventions.md),
[`../development/nene2-compliance.md`](../development/nene2-compliance.md).
Do not delete items to pass; mark `N/A` only when genuinely not applicable.

## Checklist

- [ ] `declare(strict_types=1);` in every new PHP file; `final readonly` where applicable; namespace `NeneField\`.
- [ ] Code placed in its **domain folder** (`src/Report/…`), not a layer folder (`Handlers/`, `UseCases/`, `Repositories/`).
- [ ] Layering respected: Handler (thin) → UseCase → RepositoryInterface → `Pdo*Repository`; no SQL in use case, no HTTP/superglobals in use case, no business rule in handler/middleware.
- [ ] Use case has one `execute(XxxInput): XxxOutput`; input/output are `final readonly` DTOs (no raw arrays/PSR-7).
- [ ] Reuses NENE2 shared objects (`JsonResponseFactory`, `DatabaseQueryExecutorInterface`, `ProblemDetailsResponseFactory`, `ValidationException`, `ClockInterface`, `PaginationQueryParser`, `BearerTokenMiddleware`) instead of reinventing.
- [ ] DI via a focused `XxxServiceProvider` binding interfaces to factories; container never used as a service locator in domain/use-case code.
- [ ] Every public route is in `docs/openapi/openapi.yaml` with stable `operationId`, success example, and Problem Details responses; auth required except `/health` and `/auth/login`.
- [ ] Errors are RFC 9457 Problem Details (`application/problem+json`); slugs registered in `terms.md §7`; validation → `validation-failed` (422) with structured `errors[]`.
- [ ] No leak of SQL, stack traces, file/storage paths, secrets, or private ids in any response.
- [ ] **Multi-tenancy (ADR 0013 / `multi-tenancy.md`):** org is resolved from the request into `RequestScopedHolder` (never from client input); **every** tenanted statement includes `organization_id = ?`; JWT org == resolved org (else 403); only `superadmin` is cross-tenant on bypass routes.
- [ ] **Tenant isolation tested:** a row created under org A is invisible/unmodifiable (404/403) when the resolved org is B; repository fakes are org-scoped; resolution failures map to `org-not-resolved`/`org-not-found`/`org-inactive`.
- [ ] New tenanted table has `organization_id NOT NULL` + index; per-tenant (not global) uniqueness for fields like `users.email`.
- [ ] RBAC enforced in use case / capability middleware; passwords bcrypt cost ≥ 12.
- [ ] **Audit (ADR 0014 / `audit-logging.md`):** every mutation writes an `AuditEvent` in the **same transaction**, recorded in the UseCase via `AuditRecorder` with `actor_id`; before/after are **sanitized snapshots** (no secrets/tokens/raw bytes); meaningful state verb used (`report.submitted` not generic `*.updated`); `event_name` registered in `terms.md §8`; audit append-only (no update/hard-delete); viewer admin/superadmin-only.
- [ ] Instants stored UTC via `ClockInterface`, displayed JST; no ad-hoc `new DateTimeImmutable()`.
- [ ] Schema: Phinx migration + `database/schema/{table}.sql` snapshot; snake_case plural tables; both SQLite and MySQL supported (no engine-specific SQL without ADR).
- [ ] SQL only in `Pdo*Repository`; rows cast to typed PHP; multi-statement writes use `DatabaseTransactionManagerInterface`.
- [ ] AI calls isolated in `src/AiSummary/`; only `reports.body` sent; submission never fails on AI error; cross-border → APPI §28 path (ADR 0009).
- [ ] Sibling links are read-only HTTP (`src/Upstream/`); no shared DB, no sibling model import, graceful degradation offline.
- [ ] Tests: use-case tests run without a DB (in-memory fakes); repository tests hit the test DB; contract tests vs OpenAPI; `tests/` mirrors `src/`.
- [ ] Identifiers match `docs/terms.md` (added there in the same PR if new); no forbidden suffixes; no camelCase JSON; no float money.
- [ ] `composer check` (+ `composer openapi`) passes; legal self-review run if the change touches reports/audit/AI/retention/export/user-facing copy.
