# Naming Conventions

Authoritative naming **patterns** for NeNe Field code, API contracts, database
objects, tests, and English documentation.

> **Absolute adherence — non-negotiable.** These rules are **MUST**, not
> suggestions. A name that violates a rule here, or a typo / spelling variant of a
> registered term, is a defect and **blocks merge**. There is no "close enough."
>
> This document defines the *patterns*; the **single source of truth for the exact
> strings** is [`../terms.md`](../terms.md). Introducing or renaming any identifier
> **MUST** update `terms.md` in the same PR.

**Framework baseline:** NENE2 `domain-layer.md` + `database-migrations.md`. This is
the NeNe Field override/extension list. See also
[`backend-standards.md`](./backend-standards.md),
[`frontend-standards.md`](./frontend-standards.md).

---

## 1. PHP

### Files, namespaces, modules

| Item | Rule | Example |
| --- | --- | --- |
| Namespace root | `NeneField\` | `NeneField\Report\SubmitReportHandler` |
| Domain folder | PascalCase singular | `src/Report/`, `src/ReportTemplate/`, `src/User/` |
| File name | Match the primary class | `SubmitReportHandler.php` |
| One public class per file | Required | — |
| Module folders | Domain-grouped **only** | `Report/`, `Auth/`, `Export/` — never `Handlers/`, `UseCases/`, `Repositories/` |

### Classes & interfaces

| Role | Pattern | Example |
| --- | --- | --- |
| HTTP handler | `{Verb}{Noun}Handler` | `SubmitReportHandler`, `ListReportsHandler` |
| Use case interface | `{Verb}{Noun}UseCaseInterface` | `ApproveReportUseCaseInterface` |
| Use case impl | `{Verb}{Noun}UseCase` | `ApproveReportUseCase` |
| Use case method | Always `execute` | `execute(ApproveReportInput $i): ApproveReportOutput` |
| Input DTO | `{Verb}{Noun}Input` | `SubmitReportInput` |
| Output DTO | `{Verb}{Noun}Output` | `SubmitReportOutput` |
| Domain entity | Singular noun, no suffix | `Report`, `ReportTemplate`, `User` |
| Repository interface | `{Entity}RepositoryInterface` | `ReportRepositoryInterface` |
| PDO repository | `Pdo{Entity}Repository` | `PdoReportRepository` |
| Domain exception | `{Entity}{Reason}Exception` | `ReportNotInSubmittedStateException` |
| Service provider | `{Purpose}ServiceProvider` | `ReportServiceProvider` |

All application classes: `final` and `readonly` where applicable. Every PHP file:
`declare(strict_types=1);`. **Forbidden suffixes:** `Controller`, `Service`,
`Manager`, `Repo` (`terms.md §4`).

### Methods, properties, constants

| Item | Rule | Example |
| --- | --- | --- |
| Methods | camelCase, **domain verbs** | `findById`, `save` — not `selectById`, `insertRow` |
| Properties | camelCase | `$reportRepository` |
| Constants | UPPER_SNAKE_CASE | `MAX_ATTACHMENTS` |

---

## 2. HTTP routes & OpenAPI

| Item | Rule | Example |
| --- | --- | --- |
| Path segments | lowercase **kebab-case**, plural collections | `/reports`, `/audit-events` |
| Single resource | `{id}` path param | `/reports/{report_id}` |
| Sub-action | noun/verb sub-path | `/reports/{report_id}/submit` |
| `operationId` | camelCase `{verb}{Resource}[ById]` | `submitReport`, `getReportById` |
| Response schema | `{Resource}Response` / `{Resource}ListResponse` | `ReportResponse` |
| Create request | `Create{Resource}Request` | `CreateReportRequest` |

`operationId` must match between `docs/openapi/openapi.yaml`, route registration,
and `docs/mcp/tools.json`. Never rename a shipped `operationId` — deprecate instead.
Public OpenAPI summaries/descriptions/examples: **English only** (ADR 0006).
Path segments are registered in `terms.md §6`.

---

## 3. JSON (request & response bodies)

| Item | Rule | Example |
| --- | --- | --- |
| Property names | **snake_case** | `report_id`, `work_date`, `submitted_at` |
| Booleans | `is_` / `has_` prefix | `is_active`, `ai_summary_enabled` |
| Timestamps | `_at` suffix, ISO 8601 (UTC) | `submitted_at`, `approved_at` |
| Foreign keys | `{entity}_id` | `template_id`, `organization_id` |
| List envelope | `items`, `limit`, `offset` | NENE2 pagination pattern |

**Forbidden:** camelCase (`reportId`, `workDate`), `org_id`/`orgId` (use
`organization_id`) — `terms.md §5/§9`.

---

## 4. Problem Details

| Item | Rule | Example |
| --- | --- | --- |
| Base URL | `https://nene-field.dev/problems/` | — |
| Type slug | kebab-case (registered) | `report-not-found`, `validation-failed` |
| `errors[].field` | snake_case path | `body.work_date` |
| `errors[].code` | snake_case | `required`, `invalid_status` |

Slugs are registered in `terms.md §7`. `title`/`detail`: English.

---

## 5. Database

| Item | Rule | Example |
| --- | --- | --- |
| Table names | snake_case, **plural** | `reports`, `report_templates`, `audit_events` |
| Column names | snake_case | `work_date`, `ai_summary` |
| Foreign key column | `{singular_entity}_id` | `template_id`, `organization_id` |
| Index names | `idx_{table}_{columns}` | `idx_reports_organization_id` |
| Unique constraints | `uniq_{table}_{columns}` | `uniq_users_email_org` |
| Migration file | `YYYYMMDDHHMMSS_snake_description.php` | `20260615120000_create_reports_table.php` |
| Schema snapshot | `database/schema/{table}.sql` | `database/schema/reports.sql` |

SQL lives only in `Pdo*Repository`. No camelCase columns; no `org_id` shorthand.

---

## 6. Audit events & lifecycle states

- Lifecycle: `draft` / `submitted` / `approved` / `rejected` (`terms.md §2`).
  **Forbidden:** `done`/`complete` for status, `pending` for `submitted`.
- Audit event names: `report.created`, `report.submitted`, `report.approved`, … —
  registered in `terms.md §8`.

---

## 7. Environment variables

| Item | Rule | Example |
| --- | --- | --- |
| Names | UPPER_SNAKE_CASE | `DB_HOST`, `NENE_FIELD_PORT` |
| Product prefix | `NENE_FIELD_` | `NENE_FIELD_AI_API_URL` |
| Secrets | Never commit; document in `.env.example` only | — |

---

## 8. Tests

| Item | Rule | Example |
| --- | --- | --- |
| Test class | `{ClassUnderTest}Test` | `SubmitReportUseCaseTest` |
| Test method | `test_{behavior}_when_{condition}` | `test_rejects_edit_when_report_approved` |
| Namespace | Mirror `src/` under `tests/` | `tests/Report/SubmitReportUseCaseTest.php` |

---

## 9. Frontend

| Item | Rule |
| --- | --- |
| Component file & export | PascalCase, **named export** (no default) |
| Hooks | camelCase `use` prefix (`useListReports`) |
| Entity folder | kebab-case = OpenAPI tag (`report`, `report-template`) |
| API client | Maps snake_case JSON; **never renames API fields in transit** |

Full rules: [`frontend-standards.md`](./frontend-standards.md).

---

## 10. Prohibited (blocks merge)

- Typos/variants of any term in `terms.md`; unregistered identifiers not added in
  the same PR.
- Layer-first folders (`src/Handlers/`, `src/Repositories/`); SQL outside
  `Pdo*Repository`.
- camelCase in public JSON; float/DECIMAL for money.
- Renaming a shipped `operationId`.
- `Controller`/`Service`/`Manager`/`Repo` class suffixes.
