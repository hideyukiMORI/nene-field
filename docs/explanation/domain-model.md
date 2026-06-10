# Domain Model

## Entities

### Report

The central entity. One report per user per day (soft constraint — operator-configurable).

| Field | Type | Notes |
| --- | --- | --- |
| `report_id` | UUID | Primary key |
| `organization_id` | UUID | Tenant scope |
| `user_id` | UUID | Submitter |
| `template_id` | UUID / null | Applied template (nullable — free-form allowed) |
| `title` | string | Report title or auto-generated from date + user |
| `body` | text | Main report text |
| `work_date` | date | Date the work was performed (may differ from submitted_at) |
| `status` | enum | `draft` / `submitted` / `approved` / `rejected` |
| `tags` | JSON array | User-set tags |
| `project_code` | string / null | Optional project or site code |
| `invoice_work_order_id` | string / null | HTTP reference to `nene-invoice` |
| `records_entity_id` | string / null | HTTP reference to `nene-records` |
| `ai_summary` | text / null | AI-generated one-line summary |
| `ai_tags` | JSON array / null | AI-extracted keyword tags |
| `submitted_at` | datetime / null | Submission timestamp |
| `approved_at` | datetime / null | Approval timestamp |
| `rejected_at` | datetime / null | Rejection timestamp |
| `approver_id` | UUID / null | Who approved / rejected |
| `approver_comment` | text / null | Comment on rejection (or approval) |
| `created_at` | datetime | Row creation |
| `updated_at` | datetime | Row last update |

### ReportTemplate

Reusable form definition. Organization-scoped.

| Field | Type | Notes |
| --- | --- | --- |
| `template_id` | UUID | Primary key |
| `organization_id` | UUID | Tenant scope |
| `name` | string | Template display name |
| `description` | text / null | Template description |
| `fields` | JSON | Array of field definitions (label, type, required) |
| `is_default` | boolean | Default template for new reports |
| `created_at` | datetime | Row creation |
| `updated_at` | datetime | Row last update |

### ReportAttachment

File attached to a Report.

| Field | Type | Notes |
| --- | --- | --- |
| `attachment_id` | UUID | Primary key |
| `report_id` | UUID | Parent report |
| `organization_id` | UUID | Tenant scope (denormalized for isolation) |
| `filename` | string | Original filename |
| `mime_type` | string | MIME type (image/jpeg, image/png, application/pdf) |
| `file_size` | integer | Bytes |
| `storage_path` | string | Internal storage path (never in API response) |
| `sha256` | string | File integrity hash |
| `uploaded_by` | UUID | User who uploaded |
| `created_at` | datetime | Upload timestamp |

### Organization

Tenant entity. One installation may host multiple organizations.

| Field | Type | Notes |
| --- | --- | --- |
| `organization_id` | UUID | Primary key |
| `name` | string | Organization display name |
| `ai_summary_enabled` | boolean | AI summary feature toggle |
| `ai_api_url` | string / null | External AI API endpoint |
| `notification_email` | string / null | Default notification recipient |
| `webhook_url` | string / null | Slack-compatible webhook |
| `created_at` | datetime | |
| `updated_at` | datetime | |

### User

Platform user. One user belongs to one organization.

| Field | Type | Notes |
| --- | --- | --- |
| `user_id` | UUID | Primary key |
| `organization_id` | UUID | Tenant scope |
| `name` | string | Display name |
| `email` | string | Login email (unique per org) |
| `password_hash` | string | bcrypt hash |
| `role` | enum | `submitter` / `approver` / `admin` / `superadmin` |
| `is_active` | boolean | Soft disable without delete |
| `created_at` | datetime | |
| `updated_at` | datetime | |

### AuditEvent

Immutable record of every significant mutation.

| Field | Type | Notes |
| --- | --- | --- |
| `event_id` | UUID | Primary key |
| `organization_id` | UUID | Tenant scope |
| `entity_type` | string | `Report` / `ReportAttachment` / `User` |
| `entity_id` | UUID | Affected entity |
| `event_name` | string | See `docs/terms.md §8` |
| `actor_id` | UUID | User who performed the action |
| `before` | JSON / null | Entity state before mutation |
| `after` | JSON / null | Entity state after mutation |
| `occurred_at` | datetime | Event timestamp |

---

## Report lifecycle

```
                 ┌──────────┐
                 │  draft   │ ← created (submit form, save)
                 └────┬─────┘
                      │ submit
                      ▼
                 ┌──────────┐
                 │submitted │ ← pending approval
                 └────┬─────┘
           approve /  │  \ reject
                      │    \
               ▼            ▼
         ┌──────────┐  ┌──────────┐
         │ approved │  │ rejected │ → submitter may edit & resubmit
         └──────────┘  └──────────┘
```

- A `draft` can be edited or deleted by the submitter.
- A `submitted` report cannot be edited; it must be rejected first, then the submitter edits the draft.
- An `approved` report is immutable.
- A `rejected` report transitions back to `draft` for editing.
