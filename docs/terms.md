# Terms — Canonical Identifier Registry

> **Single source of truth** for all identifier spellings used in code, database,
> API, tests, and documentation. Every identifier MUST match this registry exactly.
> Typos, spelling variants, and unregistered names block merge.
>
> Before writing any identifier, look it up here. If it is not registered, add it
> in the same PR and commit.

---

## 1. Core entities

| Canonical name | Type | Notes |
| --- | --- | --- |
| `Report` | Entity / DB table: `reports` | A single daily report |
| `ReportTemplate` | Entity / DB table: `report_templates` | Reusable form template |
| `ReportAttachment` | Entity / DB table: `report_attachments` | File attached to a report |
| `Organization` | Entity / DB table: `organizations` | Tenant |
| `User` | Entity / DB table: `users` | Platform user |
| `AuditEvent` | Entity / DB table: `audit_events` | Immutable audit record |

---

## 2. Report lifecycle states

| Canonical value | Type | Meaning |
| --- | --- | --- |
| `draft` | `reports.status` | Saved but not submitted |
| `submitted` | `reports.status` | Submitted, pending approval |
| `approved` | `reports.status` | Approved by approver |
| `rejected` | `reports.status` | Rejected; submitter may revise |

---

## 3. User roles

| Canonical value | Type | Meaning |
| --- | --- | --- |
| `submitter` | `users.role` | Can submit reports |
| `approver` | `users.role` | Can approve / reject reports |
| `admin` | `users.role` | Full management access |
| `superadmin` | `users.role` | Cross-organization access |

---

## 4. Layering suffixes (PHP class naming)

| Pattern | Example | Notes |
| --- | --- | --- |
| `XxxHandler` | `SubmitReportHandler` | HTTP request handler |
| `XxxUseCase` | `ApproveReportUseCase` | Business logic |
| `XxxRepositoryInterface` | `ReportRepositoryInterface` | Domain interface |
| `PdoXxxRepository` | `PdoReportRepository` | PDO implementation |
| `XxxServiceProvider` | `ReportServiceProvider` | DI wiring |

Forbidden: `Controller`, `Service`, `Manager`, `Repo`

---

## 5. JSON field naming (always snake_case)

| Canonical name | Type | Notes |
| --- | --- | --- |
| `organization_id` | UUID / string | Tenant scope key — every tenanted table |
| `report_id` | UUID | Primary key of Report |
| `user_id` | UUID | Primary key of User |
| `template_id` | UUID | Primary key of ReportTemplate |
| `submitted_at` | ISO 8601 | Submission timestamp |
| `approved_at` | ISO 8601 | Approval timestamp |
| `rejected_at` | ISO 8601 | Rejection timestamp |
| `created_at` | ISO 8601 | Row creation timestamp |
| `updated_at` | ISO 8601 | Row last-update timestamp |
| `ai_summary` | string / null | One-line AI-generated summary |
| `ai_tags` | string[] / null | AI-extracted keyword tags |

Forbidden: `orgId`, `reportId`, `userId`, camelCase in JSON

---

## 6. API path segments

| Canonical segment | Example path | Notes |
| --- | --- | --- |
| `reports` | `/reports` | Report collection |
| `templates` | `/templates` | ReportTemplate collection |
| `attachments` | `/reports/{report_id}/attachments` | Nested under report |
| `users` | `/users` | User collection |
| `audit-events` | `/audit-events` | Audit log |
| `export` | `/export/csv` | Data export |
| `health` | `/health` | Health check |
| `auth` | `/auth/login`, `/auth/logout`, `/auth/me` | Authentication |

---

## 7. Problem Details slugs

| Slug | HTTP | Meaning |
| --- | --- | --- |
| `report-not-found` | 404 | Report does not exist or not accessible |
| `template-not-found` | 404 | Template does not exist |
| `user-not-found` | 404 | User does not exist |
| `validation-failed` | 422 | Request body / query validation failure |
| `unauthorized` | 401 | Missing or invalid token |
| `forbidden` | 403 | Authenticated but not authorized for this action |
| `report-not-in-submitted-state` | 409 | Approve / reject on wrong lifecycle state |
| `report-not-editable` | 409 | Edit attempted on non-draft report |

---

## 8. Audit event names

| Canonical event | Entity | Trigger |
| --- | --- | --- |
| `report.created` | Report | Draft created |
| `report.submitted` | Report | Submitted for approval |
| `report.approved` | Report | Approved |
| `report.rejected` | Report | Rejected |
| `report.edited` | Report | Draft edited |
| `report.deleted` | Report | Draft deleted |
| `attachment.uploaded` | ReportAttachment | File uploaded |
| `attachment.deleted` | ReportAttachment | File deleted |
| `user.created` | User | User account created |
| `user.updated` | User | User account updated |
| `user.deleted` | User | User account deleted |

---

## 9. Common禁止スペル早見表 (banned spellings)

| Banned | Use instead |
| --- | --- |
| `org_id` | `organization_id` |
| `orgId` | `organization_id` |
| `Controller` suffix | `Handler` |
| `Service` suffix | `UseCase` |
| `Repo` abbreviation | `RepositoryInterface` / `PdoXxxRepository` |
| camelCase JSON key | snake_case (e.g., `reportId` → `report_id`) |
| `done` / `complete` as status | `approved` |
| `pending` as status | `submitted` |
