# Audit Logging Architecture — Binding

Every mutating operation in NeNe Field records **who changed what, and how —
including the state before and after the change**. The audit trail is the
tamper-evident record that lets an operator (or a reviewer) reconstruct the full
history of any report, template, user, or organization.

> **Status: binding.** A mutation that does not write an `AuditEvent` **in the same
> database transaction** is a defect that blocks merge to `main`. This adopts the
> sibling **nene-invoice** ADR 0008 pattern and is recorded in
> [ADR 0014](../adr/0014-audit-logging.md).

**Read with:** [`backend-standards.md`](./backend-standards.md) §8,
[`multi-tenancy.md`](./multi-tenancy.md),
[`../explanation/domain-model.md`](../explanation/domain-model.md) (`AuditEvent`),
[`../terms.md`](../terms.md) §8 (event names), legal positioning
[`../explanation/legal-compliance.md`](../explanation/legal-compliance.md) §5/§6.
Self-review: [`../review/backend.md`](../review/backend.md).

---

## 1. Governing principle

1. **Audit everything that mutates.** Every create / update / delete and every
   business state transition records an `AuditEvent`. Reads are not audited
   (data egress — CSV export — is, see §4).
2. **Record who, what, and how.** Actor, entity, action, **and the before/after
   state**, so the exact field-level change is reconstructable.
3. **Atomic with the change.** The `AuditEvent` is written in the **same DB
   transaction** as the mutation (NF10). If the mutation commits, the audit row
   commits; if the audit write fails, the mutation rolls back. There is no
   "best-effort, after the fact" audit.
4. **Immutable.** Audit rows are append-only — never updated, never hard-deleted.
5. **Never leak secrets.** before/after are **sanitized snapshots**; secrets are
   never written (§5).

---

## 2. What is recorded — the `AuditEvent`

One row per mutating operation (table `audit_events`; see `domain-model.md` and
`terms.md §1/§8`):

| Field | Meaning |
| --- | --- |
| `event_id` | PK |
| `organization_id` | Tenant the change belongs to (multi-tenancy §6) |
| `actor_id` | Authenticated user who performed it; **null = system** |
| `event_name` | `{entity}.{verb}` from `terms.md §8` (e.g. `report.approved`) |
| `entity_type` | `Report` / `ReportTemplate` / `ReportAttachment` / `User` / `Organization` |
| `entity_id` | The affected entity |
| `before` | Sanitized snapshot **before** the change — `null` for create |
| `after` | Sanitized snapshot **after** the change — `null` for delete |
| `request_id` | Correlation id (ties the event to app logs; from the request-id middleware) |
| `occurred_at` | UTC instant (ADR 0011) |

The **field-level diff** ("how it changed") is derived by comparing `before` and
`after`; both are full sanitized snapshots, so every changed field is visible
with its old → new value.

---

## 3. Where recording happens — `AuditRecorder` in the UseCase

Recording is done in the **UseCase layer** via an injected `AuditRecorder`, not in
middleware and not in the repository:

- **Middleware** sees the HTTP request but not the domain before/after state and
  cannot name the entity/business action — rejected.
- **Repository** knows the row but not the actor (request/auth context) nor the
  business action — rejected.
- **UseCase** has all three: the tenant + actor context, the before/after entity
  state, and the business action name. ✅

```
Handler                      UseCase                         Repository
  reads actor_id from   →   1. fetch BEFORE snapshot     →   SELECT … (org-scoped)
  the auth context          2. apply the mutation        →   INSERT/UPDATE/DELETE
  (token claims)            3. build AFTER snapshot
                            4. auditRecorder->record(            INSERT audit_events
                                 event_name, entity, before,  ┐  ── all inside ONE
                                 after, actor_id, org_id)     ┘     transaction
```

- The handler passes `actor_id` (from the JWT/auth context) into the use case
  input; the use case never reads HTTP/superglobals itself.
- The mutation and the `auditRecorder->record(...)` call run inside a single
  `DatabaseTransactionManagerInterface` boundary.
- New domains record audit **from the start** — there is no "add audit later".

---

## 4. Coverage (event matrix)

Audited operations and their `event_name` (all registered in `terms.md §8`):

| Area | Events |
| --- | --- |
| Report lifecycle | `report.created`, `report.edited`, `report.submitted`, `report.approved`, `report.rejected`, `report.deleted` |
| Attachments | `attachment.uploaded`, `attachment.deleted` |
| Templates | `template.created`, `template.updated`, `template.deleted` |
| Users | `user.created`, `user.updated`, `user.deleted` |
| Organization | `organization.updated` (incl. AI/notification settings changes) |
| Data egress | `report.exported` (CSV export — `after` carries the filter/range, not the rows) |
| Authentication | `auth.login_succeeded`, `auth.login_failed` (security trail; `actor_id` = the user or null) |

Rules:

- **State transitions are audited with the meaningful verb**, not a generic
  "updated" (e.g. submit → `report.submitted`, not `report.edited`).
- Soft/explicit deletes record `*.deleted` with the `before` snapshot.
- `report.exported` records *that* an export happened and its parameters — never
  the exported row contents (would duplicate PII unnecessarily).

---

## 5. Sanitized snapshots — what is NEVER recorded

before/after are produced by the **same presenters used for API responses**, so
non-public fields never reach the audit log. The following are **never** written
to `before`/`after` (or anywhere in `audit_events`):

- `password_hash`, any password or secret.
- JWTs, bearer/service tokens, API keys (`NENE_FIELD_AI_API_KEY`, webhook secrets),
  attachment download tokens or their hashes.
- Raw attachment bytes or internal `storage_path` (record `filename`, `mime_type`,
  `file_size`, `sha256` instead).

**Audit store ≠ debug logs.** The legal rule "never log report bodies / tokens in
production logs" (`legal-compliance.md` §4–§5) is about *application/debug logs*.
The audit store legitimately holds before/after business state (which may include
report `body` and other PII) because it is **access-controlled (admin/superadmin
only, §7), tenant-scoped, immutable, and retained** as the evidential record.
Secrets are still excluded everywhere.

---

## 6. Immutability, integrity & time

- `audit_events` is **append-only**: no `UPDATE`, no hard `DELETE`. There is no API
  to edit an audit row.
- Atomicity (§1.3) makes the trail complete: you cannot have a mutation without its
  audit row, or vice versa.
- This is a **tamper-evident** record, **not** a certified timestamp / 認定タイムスタンプ
  (`legal-compliance.md` §5). Do not claim non-repudiation.
- `occurred_at` is a **UTC** instant from `ClockInterface` (ADR 0011); displayed in
  the operator's locale (ADR 0012).

---

## 7. Tenant scoping, viewer & retention

- `audit_events.organization_id` scopes every row; the audit viewer is tenant-scoped
  exactly like any other tenanted read (`multi-tenancy.md` §4). Only `superadmin`
  sees across tenants.
- **Read access is admin / superadmin only** (RBAC). `submitter` / `approver` cannot
  read the audit log.
- `GET /audit-events` (read-only, filterable by entity/actor/date/event) serves the
  admin viewer; an admin CSV export of the audit log is itself audited.
- **Retention:** audit events are never auto-purged; destructive actions warn first
  (ADR 0010). They are retained at least as long as the records they describe.

---

## 8. Forbidden (blocks merge)

- A mutating use case that does not record an `AuditEvent`.
- Recording the audit row **outside** the mutation's transaction (best-effort).
- Recording in middleware or a repository instead of the use case.
- Writing any secret/token/password/raw-bytes into `before`/`after` (or bypassing
  the sanitized presenter).
- `UPDATE` or hard `DELETE` on `audit_events`; an endpoint that edits audit rows.
- A generic `*.updated` event where a meaningful state verb exists
  (`report.submitted` / `report.approved` / `report.rejected`).
- An `event_name` not registered in `terms.md §8` (add it in the same PR).
