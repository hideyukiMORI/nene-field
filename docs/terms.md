# Terms — Canonical Identifier Registry & 用語一覧

> **THE single source of truth (唯一の真実) for all terminology in NeNe Field** —
> every identifier spelling used in code, database, API, tests, and documentation,
> **and** the bilingual vocabulary (用語一覧, §10). There is **no second source**:
> [`docs/explanation/glossary.md`](./explanation/glossary.md) and the naming-pattern
> doc [`docs/development/naming-conventions.md`](./development/naming-conventions.md)
> **defer to this file** for exact strings.
>
> **Absolute adherence — non-negotiable.** Every identifier MUST match this
> registry **exactly**. There is no "close enough." A typo, a spelling variant, or
> an unregistered identifier is a **defect that blocks merge** (see §11).
>
> Before writing any identifier, look it up here. If it is not registered, **add it
> in the same PR** that introduces or renames it. Renaming a shipped identifier is
> an API-compatibility decision (deprecate; do not silently rename).

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
| `organization_id` | UUID / string | Tenant scope key — every tenanted table (NOT NULL + index) |
| `slug` | string | Organization tenant-resolution key (unique) |
| `custom_domain` | string / null | Organization vanity domain (unique) |
| `is_active` | boolean | Organization active flag |
| `report_id` | UUID | Primary key of Report |
| `user_id` | UUID | Primary key of User |
| `template_id` | UUID | Primary key of ReportTemplate |
| `submitted_at` | ISO 8601 | Submission timestamp |
| `approved_at` | ISO 8601 | Approval timestamp |
| `rejected_at` | ISO 8601 | Rejection timestamp |
| `created_at` | ISO 8601 | Row creation timestamp |
| `updated_at` | ISO 8601 | Row last-update timestamp |
| `request_id` | string | Correlation id (audit ↔ application logs) |
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
| `org-not-resolved` | 404 | Tenant could not be resolved from the request (ADR 0013) |
| `org-not-found` | 404 | No organization for the resolved slug / custom domain |
| `org-inactive` | 403 | Resolved organization is inactive |

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
| `template.created` | ReportTemplate | Template created |
| `template.updated` | ReportTemplate | Template updated |
| `template.deleted` | ReportTemplate | Template deleted |
| `organization.updated` | Organization | Org settings changed (incl. AI / notification) |
| `report.exported` | Report | CSV export performed (records filters, not rows) |
| `auth.login_succeeded` | User | Successful login |
| `auth.login_failed` | User | Failed login attempt |

Recording mechanism, before/after sanitization, and same-transaction rule:
[`development/audit-logging.md`](./development/audit-logging.md) (ADR 0014).

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
| `attachement` / `attatchment` | `attachment` / `ReportAttachment` |
| `aprover` / `approuver` | `approver` |
| `organisation` (en-GB) | `organization` |
| `summery` | `summary` (`ai_summary`) |
| `recieve` | `receive` |
| `audit_log` (column/table) | `audit_events` / `AuditEvent` |

---

## 10. Vocabulary (用語一覧 — bilingual)

Authoritative bridge between Japanese product terms, English terms, and the
**canonical identifier** to use in code/API/DB. When a Japanese term is meant, use
exactly the identifier in the third column — never invent a synonym.

| Term (EN) | 日本語 | Canonical identifier / value | Meaning |
| --- | --- | --- | --- |
| Daily report | 日報 | `Report` · table `reports` | A structured work record for one `work_date`, submitted by a field worker |
| Report template | 帳票テンプレート / テンプレート | `ReportTemplate` · `report_templates` | Reusable org-scoped form definition |
| Attachment | 添付ファイル | `ReportAttachment` · `report_attachments` | Photo/PDF evidence attached to a report |
| Organization (tenant) | 組織 / テナント | `Organization` · `organizations` · `organization_id` | Tenant entity; the unit of data isolation |
| User | ユーザー | `User` · `users` | Operator account within an organization |
| Audit event | 監査ログ / 監査イベント | `AuditEvent` · `audit_events` | Immutable record of a significant mutation |
| Field worker / submitter | 現場スタッフ / 提出者 | role `submitter` | Submits daily reports |
| Approver | 承認者 | role `approver` | Approves or rejects submitted reports |
| Admin | 管理者 | role `admin` | Manages users, templates, settings |
| Superadmin | 全体管理者 | role `superadmin` | Cross-organization access |
| Draft | 下書き | status `draft` | Saved but not yet submitted |
| Submitted | 提出済み | status `submitted` | Submitted, pending approval |
| Approved | 承認済み | status `approved` | Approved (immutable) |
| Rejected | 差し戻し | status `rejected` | Returned to the submitter for revision |
| Submission | 提出 | event `report.submitted` | Act of submitting a draft for approval |
| Approval | 承認 | event `report.approved` | Manager acceptance of a submitted report |
| Rejection | 差し戻し | event `report.rejected` | Manager action returning a report for revision |
| Work date | 作業日 | `work_date` | Date the reported work was performed |
| Project code | プロジェクトコード / 現場コード | `project_code` | Optional project/site code |
| AI summary | AI 要約 | `ai_summary` / `ai_tags` | Opt-in one-line summary + keyword tags |
| Webhook | Webhook | `webhook_url` | Slack-compatible HTTP notification callback |
| CSV export | CSV エクスポート | path `/export/csv` | Report-data export for payroll/billing |
| Work order link | 作業指示リンク | `invoice_work_order_id` | Read-only HTTP reference to `nene-invoice` |
| Records entity link | レコード連携 | `records_entity_id` | Read-only HTTP reference to `nene-records` |

New product concepts are added here in the same PR that introduces them.

---

## 11. Typo & spelling discipline (厳守 — binding)

The single greatest threat to a terminology SSOT is silent drift. The following is
**MUST**, not guidance:

1. **Exact match only.** Every identifier in code, DB, API JSON, OpenAPI, tests,
   and English docs MUST match this file **character-for-character**. No casing
   variants, no abbreviations, no synonyms, no en-GB/en-US drift.
2. **Blocks merge.** A typo, a spelling variant, or an identifier not registered
   here is a defect that **blocks merge to `main`** — there is no temporary
   exception and no ADR escape hatch for a typo.
3. **Register-in-same-PR.** Any new or renamed identifier MUST be added to this
   file in the **same PR**. A PR that introduces an unregistered identifier fails
   review.
4. **One source.** Do not restate the canonical spelling as authoritative anywhere
   else. Other docs reference this file; they never redefine a term.
5. **Check the banned list (§9) before naming.** When a banned spelling fits what
   you were about to write, stop and use the canonical form.

**Enforcement.** Every self-review checklist
([`review/backend.md`](./review/backend.md), [`review/frontend.md`](./review/frontend.md),
[`development/self-review.md`](./development/self-review.md)) carries a terms check;
reviewers reject violations. When backend tooling lands, a CI term-lint will scan
for §9 banned spellings and unregistered identifiers as a hard gate; until then the
manual review gate is mandatory.
