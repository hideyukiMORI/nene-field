# Requirements

Functional and non-functional requirements for NeNe Field MVP.

## Functional Requirements

### Report Submission (Submitter)

- R1.1 A submitter can create a draft report with title, body text, work date, tags, and optional project code.
- R1.2 A submitter can select a report template to pre-fill field structure.
- R1.3 A submitter can attach up to 5 files (photo/PDF) per report. Max size: 5 MB per file.
- R1.4 A submitter can save a draft without submitting.
- R1.5 A submitter can edit or delete a draft before submission.
- R1.6 A submitter can submit a draft for approval.
- R1.7 A submitter can see the list of their own reports with status and date.
- R1.8 A submitter can view a rejected report with the approver's comment and resubmit.

### Approval Workflow (Approver)

- R2.1 An approver can see all submitted reports in their organization (Phase 1; team-scoped in Phase 2).
- R2.2 An approver can filter reports by submitter, work date range, tag, and status.
- R2.3 An approver can approve a submitted report (with optional comment).
- R2.4 An approver can reject a submitted report with a mandatory comment.
- R2.5 An approver receives an email notification when a report is submitted.

### Manager / Admin

- R3.1 An admin can create, edit, and delete report templates.
- R3.2 An admin can manage users (create, update role, disable, delete).
- R3.3 An admin can export report data as CSV (date range, user, project code filters).
- R3.4 An admin can view the audit event log.
- R3.5 An admin can configure organization settings (AI summary, webhook URL, notification email).

### AI Summary (Optional)

- R4.1 When AI summary is enabled for the organization, a one-line summary and keyword tags are generated on submission.
- R4.2 The summary is stored and displayed alongside the report body.
- R4.3 An admin can regenerate or clear the summary.
- R4.4 AI summary is clearly labeled as AI-generated in the UI.

### Authentication

- R5.1 Users authenticate via email + password (JWT issued, valid 24 hours by default).
- R5.2 `organization_id` is embedded in the JWT and enforced on all tenanted endpoints.
- R5.3 Role is embedded in the JWT and enforced on protected endpoints.

---

## Non-Functional Requirements

### Mobile Performance

- NF1 The report submission form must be fully functional on iOS 15+ Safari and Android 10+ Chrome.
- NF2 Photo upload must succeed on typical Japanese mobile network speeds (3–4G).
- NF3 Images uploaded from mobile must be compressed server-side to ≤ 1 MB for storage efficiency.
- NF4 The submission form must complete in under 3 minutes for a typical report.

### Security

- NF5 All API endpoints must require JWT authentication except `/health` and `/auth/login`.
- NF6 Every tenanted query must include `organization_id` in the WHERE clause.
- NF7 File paths must never appear in API responses; files are served via authenticated endpoints.
- NF8 Report body must never be sent to an external AI API without explicit organization opt-in.
- NF9 Passwords must be hashed with bcrypt (cost ≥ 12).

### Data Integrity

- NF10 Every significant mutation must create an `AuditEvent` record in the same DB transaction.
- NF11 Attachments must be SHA-256 verified on download.
- NF12 An approved report is immutable; no edits are permitted.

### Availability

- NF13 Tier A (shared hosting): same uptime characteristics as the host. No additional SLA.
- NF14 Tier B (Docker): operator-managed. Health check at `GET /health` for monitoring.

### Privacy

- NF15 AI summary sends only the report `body` text. No metadata (user name, date, project).
- NF16 Operator can delete a user and their data on request (GDPR-adjacent; no statutory requirement in Japan, but good practice).
- NF17 `.env` must document which fields are PII.
