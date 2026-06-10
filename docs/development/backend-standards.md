# Backend Standards — Binding

NeNe Field backend policy for PHP API code. NeNe Field is a **NENE2 consumer
project**: it builds on the framework, it does not fork it.

> **Status: binding.** Violations of placement, layering, dependency direction,
> multi-tenancy, naming, or security rules **block merge to `main`**. Deviations
> require a local **ADR**.

**Framework baseline (authoritative):** NENE2 `docs/development/` — installed at
`vendor/hideyukimori/nene2/` and mirrored in the `../NENE2` source repo. When this
document is silent, **NENE2 wins**; deviate from NENE2 only with a local ADR.

**Read with:** [`naming-conventions.md`](./naming-conventions.md),
[`nene2-compliance.md`](./nene2-compliance.md), [`coding-standards.md`](./coding-standards.md),
identifiers [`../terms.md`](../terms.md), binding scope/legal
[`../explanation/scope-contract.md`](../explanation/scope-contract.md) ·
[`../explanation/legal-compliance.md`](../explanation/legal-compliance.md).
Self-review: [`../review/backend.md`](../review/backend.md).

---

## 1. Project shape

```
vendor/hideyukimori/nene2/   ← framework (NEVER edit)
src/                         ← product code (namespace NeneField\)
tests/                       ← mirrors src/ 1:1
database/migrations/         ← Phinx migrations
database/seeds/              ← dev/local seeds (no production/private data)
database/schema/             ← per-table schema snapshots
config/                      ← non-secret config; secrets via env only
docs/openapi/openapi.yaml    ← public API contract (source of truth)
public_html/index.php        ← front controller (only exposed directory)
```

- Namespace root: **`NeneField\`**.
- PHP **8.4**, `declare(strict_types=1);` in **every** file. PSR-12 via PHP-CS-Fixer.
- PHPStan **level 8** (max). `composer check` is the gate before every PR.
- Never edit `vendor/`. Never expose `src/`, `vendor/`, `.env`, tests, or frontend
  source under `public_html/`.

---

## 2. Module layout (domain-grouped — zero tolerance)

Group by **domain concept, not technical layer**. A single concept's handler,
use case, repository, DTOs, domain object, and exceptions live together in one
folder. **Layer-first folders (`src/Handlers/`, `src/UseCases/`,
`src/Repositories/`) are forbidden and block merge.**

```
src/
  ApplicationServiceProvider.php   # registers domain service providers
  Http/                            # product HTTP glue (routes registrar, shared handlers)
  Organization/                    # tenant entity + per-request org resolution
  Auth/                            # JWT login/logout/me, Role/RBAC, capability checks
  User/                            # operator accounts within an organization
  Report/                          # reports + lifecycle (draft→submitted→approved/rejected)
  ReportTemplate/                  # org-scoped form templates
  ReportAttachment/                # file upload/download, sha256, storage
  AuditEvent/                      # immutable audit log
  Export/                          # CSV export
  AiSummary/                       # external AI summary (opt-in; ADR 0007/0009)
  Upstream/                        # optional read-only HTTP clients (Invoice, Records)
```

A typical domain folder (one public class per file, file name = class name):

```
src/Report/
  SubmitReportInput.php            # final readonly DTO
  SubmitReportOutput.php           # final readonly DTO
  SubmitReportUseCaseInterface.php # one method: execute()
  SubmitReportUseCase.php          # business rules
  Report.php                       # readonly domain object
  ReportRepositoryInterface.php    # domain data contract
  PdoReportRepository.php          # the ONLY place SQL lives
  ReportNotInSubmittedStateException.php
  SubmitReportHandler.php          # thin HTTP handler
  ReportServiceProvider.php        # DI wiring for this domain
```

---

## 3. Layering & data flow (binding)

```
HTTP request
  → Middleware            (size, content-type, JSON parse, auth, RBAC, request-id, CORS)
  → Handler               (parse input, build readonly DTO, call use case, map response)
    → UseCase              (business invariants, authorization rules, state transitions)
      → RepositoryInterface (domain data contract)
        → PdoXxxRepository  (SQL only; casts rows to typed values)
```

| Layer | May | Must NOT |
| --- | --- | --- |
| **Handler** | Read PSR-7, validate format, build DTO, call one use case, return JSON | SQL, business rules, call repositories directly, AI/HTTP calls |
| **UseCase** | Business invariants, authorization, orchestration, state rules | `$_SERVER`/`$_ENV`/superglobals, PDO/SQL, PSR-7, container as locator |
| **Repository iface** | Express data access in **domain verbs** (`findById`, `save`) | Leak SQL verbs (`selectById`), return PDO rows/raw arrays |
| **PdoXxxRepository** | All SQL, parameter binding, row→type casting | Business rules, HTTP, AI calls |

Rules:

- **Constructor injection only**, typed to interfaces. No `new` for testable deps.
- Use cases expose exactly **one method, `execute(XxxInput): XxxOutput`**. Input and
  output are `final readonly` DTOs — never raw arrays or PSR-7 objects.
- Domain objects are `final readonly`, free of ORM/DB coupling; `id` nullable before persist.
- Throw **named domain exceptions** for invariant violations; map them to Problem
  Details at the HTTP boundary (§6), never inside use cases.
- No cross-layer leakage: no SQL in a use case, no HTTP in a repository, no business
  rule in middleware or a route callback.

---

## 4. Reuse framework objects (do not reinvent)

Use NENE2's shared objects; do not hand-roll equivalents. Exact symbols
(verify against `vendor/hideyukimori/nene2/`):

| Need | Use from NENE2 |
| --- | --- |
| JSON success response | `Nene2\Http\JsonResponseFactory` |
| Parse JSON request body | `Nene2\Http\JsonRequestBodyParser` |
| Routing + path params | `Nene2\Routing\Router` (read params from `Router::PARAMETERS_ATTRIBUTE`, **not** `getAttribute('id')`) |
| Problem Details errors | `Nene2\Error\ProblemDetailsResponseFactory` |
| Map domain exception → Problem Details | `Nene2\Error\DomainExceptionHandlerInterface` |
| Validation errors | `Nene2\Validation\ValidationError` / `ValidationException` (→ `validation-failed`, 422) |
| Parameterized SQL | `Nene2\Database\DatabaseQueryExecutorInterface` (never raw PDO) |
| Multi-query commit/rollback | `Nene2\Database\DatabaseTransactionManagerInterface` |
| DB constraint violations | `Nene2\Database\DatabaseConstraintException` |
| "Now" / time | `Nene2\Http\ClockInterface` (UTC; `UtcClock`) — never `new DateTimeImmutable()` ad hoc |
| List pagination | `Nene2\Http\PaginationQueryParser` + `PaginationResponse` (`items`/`limit`/`offset`) |
| Bearer JWT auth | `Nene2\Auth\BearerTokenMiddleware` (+ `TokenIssuerInterface`) — see NENE2 ADR 0008 |
| Per-request context (org/user/request-id) | `Nene2\Http\RequestScopedHolder` |
| Typed app config | `Nene2\Config\AppConfig` (raw `getenv()`/`$_ENV` only at the config-loading boundary) |
| DI registration | `Nene2\DependencyInjection\ContainerBuilder` + `ServiceProviderInterface` |

---

## 5. Dependency injection & wiring

- **Explicit wiring, no autowiring.** Each domain has one focused
  `XxxServiceProvider implements ServiceProviderInterface` that binds its
  interfaces to factories. Keep providers small and grouped by domain.
- Register domain providers from `ApplicationServiceProvider`; framework HTTP
  services come from NENE2's `RuntimeServiceProvider`. Make ordering explicit when
  it matters.
- **Bind interfaces, not concretes**, wherever test substitution matters
  (`ReportRepositoryInterface` → `PdoReportRepository`).
- **Never use the container as a service locator** inside use cases or domain
  objects. Providers must not read request-specific state.

---

## 6. HTTP, OpenAPI & errors

- Every public route appears in `docs/openapi/openapi.yaml` with a stable
  `operationId`, success schema + `ok` example, and Problem Details responses.
  OpenAPI is the contract; keep it in sync (endpoint-scaffold workflow).
- All endpoints require JWT auth **except** `/health` and `/auth/login` (NF5).
- Errors use **RFC 9457 Problem Details** (`application/problem+json`); base URL
  `https://nene-field.dev/problems/`. Slugs are kebab-case and registered in
  `docs/terms.md §7` (`report-not-found`, `validation-failed`, `forbidden`,
  `report-not-in-submitted-state`, …).
- Validation failures → `validation-failed` (422) with a structured `errors[]`
  array (`field` snake_case path, `message` English, `code` snake_case).
- **Never leak** SQL, stack traces, file paths, storage paths, secrets, or private
  identifiers in any response (NF7).

### Validation layers (binding)

```
Middleware — request size, content-type, malformed JSON, auth/RBAC, request-id, CORS
Handler    — path/query/body mapping, readonly DTO creation, format validation
UseCase    — business invariants, authorization-sensitive rules, state transitions
```

Never put route-specific business rules in middleware; never put HTTP concerns in
use cases (rules must hold for CLI/tests/MCP too).

---

## 7. Multi-tenancy & security (binding)

Full architecture: [`multi-tenancy.md`](./multi-tenancy.md) (ADR 0013). In short:

- Tenant isolation is non-negotiable. The current org is **resolved from the
  request** (`OrgResolverMiddleware` + a resolution strategy) and stored in
  `Nene2\Http\RequestScopedHolder<int>` — **never taken from client input**.
- **Every** tenanted `Pdo*Repository` includes `organization_id = ?` (from
  `$orgId->get()`) in **every** statement — SELECT/INSERT/UPDATE/DELETE/exists (NF6).
- **Org ↔ JWT consistency:** the authenticated user's `organization_id` MUST equal
  the resolved org id, else `403 forbidden`. Only `superadmin` crosses tenants, on
  bypass routes (`/health`, `/auth/`, `/organizations`, `/superadmin/`).
- Per-tenant (not global) uniqueness for fields like `users.email`
  (`uniq_users_email_org`). Tenant-isolation tests are mandatory.
- Role/RBAC (`submitter` / `approver` / `admin` / `superadmin`) is enforced in the
  use case / capability middleware — never in the browser only.
- Passwords: bcrypt cost ≥ 12 (NF9). Attachment storage paths never in API
  responses; files served only via authenticated endpoints (NF7).
- Money (if any cents fields appear): integer minimum units, never float/DECIMAL.

---

## 8. Audit, integrity & time (binding)

- **Every significant mutation writes an `AuditEvent`** (before/after) **in the
  same DB transaction** as the mutation (NF10). Audit events are immutable — no
  hard delete, no silent rewrite. Event names come from `docs/terms.md §8`.
- An **approved report is immutable** (NF12); a `submitted` report cannot be
  edited (reject back to `draft` first).
- Attachments are **SHA-256 verified** on download (NF11).
- Instants are stored in **UTC**, displayed in **JST** (ADR 0011). Use
  `ClockInterface`, never ad-hoc wall-clock reads.
- No silent auto-purge of reports/attachments/audit events; warn before
  destructive actions (ADR 0010).

> Changes to reports, audit, AI transmission, retention, attachments, export, or
> any user-facing copy MUST run [`../review/legal-compliance.md`](../review/legal-compliance.md)
> and state legal/compliance impact in the PR. No prohibited claims (`legal-compliance.md` §10).

---

## 9. Database, schema & migrations

- Migration runner: **Phinx**. Files: `database/migrations/YYYYMMDDHHMMSS_snake_description.php`.
  Each new table also ships a snapshot `database/schema/{table}.sql`.
- Tables: snake_case **plural** (`reports`, `report_templates`, `report_attachments`,
  `audit_events`). Columns: snake_case. FK column: `{singular_entity}_id`.
  Index `idx_{table}_{columns}`; unique `uniq_{table}_{columns}`.
- Tenant-scoped tables carry `organization_id` (denormalized where needed for
  isolation, per domain model).
- **Both SQLite (Tier A) and MySQL (Tier B) must be supported and tested** —
  no engine-specific SQL without an ADR (ADR 0003).
- SQL lives **only** in `Pdo*Repository` classes. Cast row values to typed PHP on
  the way out. Use `DatabaseTransactionManagerInterface` for multi-statement writes.

---

## 10. AI summary isolation (ADR 0007 / 0009)

- All external AI calls are isolated in `src/AiSummary/`. Only `reports.body` is
  sent — never metadata, never attachments (NF15). AI is **opt-in per org**, off
  by default.
- AI failures are caught and logged; **report submission must never fail because
  AI failed**. Cross-border endpoints trigger the APPI §28 warning path (ADR 0009).

---

## 11. Sibling integration (HTTP reference only)

- Links to `nene-invoice` (`invoice_work_order_id`) and `nene-records`
  (`records_entity_id`) are **optional read-only HTTP references** in
  `src/Upstream/`. Never share a DB/PDO connection, never import sibling models,
  never write to a sibling, and **degrade gracefully** if a sibling is offline.

---

## 12. Testing

- **UseCase tests run without a DB** — inject in-memory repository fakes
  implementing the interface (fakes live in `tests/`, never shipped).
- **Repository tests** exercise real SQL against the test DB (SQLite in-memory;
  MySQL for adapter verification).
- **HTTP/contract tests** assert behavior against `docs/openapi/openapi.yaml`.
- `tests/` mirrors `src/` 1:1. Test class `{ClassUnderTest}Test`; method
  `test_{behavior}_when_{condition}`. Deterministic; pin time via a fixed clock.

---

## 13. Verification

```bash
composer check        # test + analyse + cs (PHPUnit, PHPStan 8, PHP-CS-Fixer)
composer test
composer analyse
composer cs
composer openapi      # OpenAPI contract validation
```

---

## 14. Prohibited (blocks merge)

- Layer-first folders (`src/Handlers/`, `src/UseCases/`, `src/Repositories/`).
- SQL outside `Pdo*Repository`; raw PDO instead of `DatabaseQueryExecutorInterface`.
- Business logic in handlers/middleware; HTTP/superglobals in use cases.
- Container used as a service locator in domain/use-case code.
- camelCase in public JSON; float/DECIMAL for money; ad-hoc `new DateTimeImmutable()`.
- Tenanted query without `organization_id` in `WHERE`.
- Mutation without an in-transaction `AuditEvent`; editing an approved report.
- Unregistered/typo'd identifiers (must match `docs/terms.md` — add in same PR).
- Editing `vendor/`; engine-specific SQL without an ADR; prohibited legal claims (§8).
