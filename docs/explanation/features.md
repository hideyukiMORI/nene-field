# Feature List

Complete feature catalogue for NeNe Field. Phase column indicates when each feature ships.

## MVP (Phase 1–2)

### F-01 Report Submission — スマホ日報提出

| Attribute | Value |
| --- | --- |
| Phase | 1 (API) / 2 (UI) |
| Actor | Submitter |
| Priority | Must-have |

- **Template selection:** Choose from organization-defined templates or use free-form input.
- **Field types in templates:** Short text, long text, number, checkbox, date, select.
- **Free body text:** Markdown-light text area for narrative description.
- **Work date:** Separate from submission timestamp; defaults to today.
- **Tags:** User-typed free tags for categorization.
- **Project code:** Optional project or site code for reporting/CSV grouping.
- **Draft save:** Save without submitting; resume later.
- **Submit:** Transition draft → submitted; triggers approval notification.

---

### F-02 Photo & File Attachment — 写真・ファイル添付

| Attribute | Value |
| --- | --- |
| Phase | 1 (API) / 2 (UI) |
| Actor | Submitter |
| Priority | Must-have |

- Upload up to **5 files** per report (JPEG, PNG, PDF).
- Server-side image compression to ≤ 1 MB for JPEG/PNG uploads.
- Files served via authenticated download URL (storage path never exposed).
- SHA-256 integrity check on download.

---

### F-03 Approval Workflow — 承認ワークフロー

| Attribute | Value |
| --- | --- |
| Phase | 1 (API) / 2 (UI) |
| Actor | Approver |
| Priority | Must-have |

- **Approve:** Single-click approval with optional comment.
- **Reject:** Rejection with mandatory comment; report returns to `draft` state.
- **Resubmit:** Submitter edits rejected draft and resubmits.
- Phase 1: Single-level approval only. Multi-level deferred to Phase 2.
- Approver sees the full report body, AI summary (if enabled), and all attachments.

---

### F-04 Report List & Search — 一覧・検索・フィルタ

| Attribute | Value |
| --- | --- |
| Phase | 1 (API) / 2 (UI) |
| Actor | Approver, Admin |
| Priority | Must-have |

- List all reports in the organization with pagination.
- **Filters:** submitter (user), work date range, tag, approval status, project code.
- **Sort:** work date desc (default), submitted_at desc.
- **My reports view:** Submitter sees only their own reports.

---

### F-05 CSV Export — CSV エクスポート

| Attribute | Value |
| --- | --- |
| Phase | 1 (API) / 2 (UI) |
| Actor | Admin |
| Priority | Must-have |

- Export all approved (and optionally all) reports as CSV.
- **Filters:** date range, user, project code, status.
- **Columns:** report_id, user_name, work_date, title, body, tags, project_code, status, submitted_at, approved_at, approver_comment.
- Attachment filenames included; files are not bundled in CSV (separate download).
- UTF-8 with BOM (Excel compatibility).

---

### F-06 Audit Log — 監査ログ

| Attribute | Value |
| --- | --- |
| Phase | 1 (API) / 2 (UI) |
| Actor | Admin |
| Priority | Must-have |

- Immutable log of: report created, submitted, approved, rejected, edited, deleted; attachment uploaded/deleted; user created/updated/deleted.
- Each entry: event name, actor, entity, timestamp, before/after snapshot.
- Searchable by entity type, actor, date range.

---

### F-07 Report Templates — テンプレート管理

| Attribute | Value |
| --- | --- |
| Phase | 1 (API) / 2 (UI) |
| Actor | Admin |
| Priority | Must-have |

- Create, edit, delete, and reorder organization templates.
- Mark one template as default.
- Field types: short text, long text, number, checkbox, date, select (enum).
- Templates are versioned — existing reports are not broken by template edits.

---

### F-08 User Management — ユーザー管理

| Attribute | Value |
| --- | --- |
| Phase | 1 (API) / 2 (UI) |
| Actor | Admin |
| Priority | Must-have |

- Create, update, disable, and delete users within the organization.
- Assign roles: `submitter`, `approver`, `admin`.
- Disable without delete (soft-disable); data is retained.
- Admin cannot elevate to `superadmin`.

---

### F-09 Organization Settings — 組織設定

| Attribute | Value |
| --- | --- |
| Phase | 1 (API) / 2 (UI) |
| Actor | Admin |
| Priority | Must-have |

- Organization name and contact email.
- AI summary toggle (off by default) + AI API URL and key.
- Email notification settings.
- Webhook URL (Slack-compatible) for submission / approval events.

---

### F-10 Email Notifications — メール通知

| Attribute | Value |
| --- | --- |
| Phase | 2 |
| Actor | System |
| Priority | Must-have |

- Notify approver when a report is submitted.
- Notify submitter when their report is approved or rejected.
- Configurable notification email per organization.
- Plain-text + HTML email via NENE2 Mailer.

---

## Phase 3 (AI + Notifications)

### F-11 AI Summary — AI 要約

| Attribute | Value |
| --- | --- |
| Phase | 3 |
| Actor | System (triggered on submission) |
| Priority | High differentiator |

- On submission, call OpenAI-compatible API with report body.
- Store one-line summary in `reports.ai_summary`.
- Store keyword tags in `reports.ai_tags` (JSON array, up to 10 tags).
- Display in manager list view and report detail.
- Admin can regenerate or clear summary.
- Only report body is sent; no metadata, no attachments.
- Requires `ai_summary_enabled = true` in Organization settings + valid API key.

---

### F-12 Webhook Notifications — Webhook 通知

| Attribute | Value |
| --- | --- |
| Phase | 3 |
| Actor | System |
| Priority | Medium |

- Slack-compatible webhook on submission, approval, rejection.
- Payload: event type, report title, submitter name, work date, AI summary (if available), deep link.
- Retry on failure (3 attempts, exponential backoff).

---

### F-13 Weekly / Monthly Summary Report

| Attribute | Value |
| --- | --- |
| Phase | 3 |
| Actor | Admin |
| Priority | Medium |

- Automated weekly / monthly summary CSV sent to organization admin email.
- Configurable: day of week (weekly), day of month (monthly).
- Covers all submitted + approved reports in the period.

---

## Phase 4 (Ecosystem)

### F-14 Invoice Work Order Link

| Attribute | Value |
| --- | --- |
| Phase | 4 |
| Actor | Submitter, Admin |
| Priority | Differentiator |

- Optional field on report: `invoice_work_order_id`.
- Validates that the referenced work order exists in `nene-invoice` via HTTP.
- Admin UI shows linked work order summary (title, project) fetched from Invoice API.
- CSV export includes `invoice_work_order_id` for billing reconciliation.

---

### F-15 MCP Read Tools

| Attribute | Value |
| --- | --- |
| Phase | 4 |
| Actor | AI agent / MCP host |
| Priority | Ecosystem |

- `searchReports` — search reports by date, user, status, tag.
- `getReportById` — fetch single report with attachments and AI summary.
- `listAuditEvents` — fetch audit log entries.
- Read-only; write tools deferred to Phase 4 followup.
