# ADR 0014: Audit Logging of All Mutating Operations

## Status

accepted

## Context

NeNe Field must let an operator (and a reviewing professional) reconstruct **who
changed what, and how** — including the **before and after** state of every change.
The domain model already defines an `AuditEvent` (with `before`/`after` JSON) and
NF10 requires an audit row per mutation in the same DB transaction, but the
*mechanism* — where recording happens, how before/after are captured without
leaking secrets, and exactly what is covered — was unspecified.

The sibling **nene-invoice** (ADR 0008) already solved this:

- Recording in the **UseCase layer** via an `AuditRecorder`, because the use case
  has the actor + tenant context, the before/after state, and the business action
  name (middleware and repositories each lack one of these).
- **before/after as sanitized snapshots** built from the same `*Response`
  presenters used for the API, so secrets (`password_hash`, tokens) are never
  written; the field-level diff is derivable from the two snapshots.
- `{entity}.{verb}` action naming; create/update/delete plus business state events.

nene-invoice recorded its audit **best-effort, outside** the mutation transaction
(it documents that a crash between the mutation and the audit write could drop an
entry, with same-transaction recording as a planned follow-up). NeNe Field treats
that limitation as unacceptable and requires same-transaction recording from day one.

## Decision

Adopt the nene-invoice pattern, documented as binding in
[`../development/audit-logging.md`](../development/audit-logging.md):

1. **Record in the UseCase via an `AuditRecorder`.** The handler passes `actor_id`
   from the auth context; the use case fetches the **before** snapshot, applies the
   mutation, builds the **after** snapshot, and records the `AuditEvent`.
2. **Same transaction (binding, NF10).** The mutation and the audit write share one
   `DatabaseTransactionManagerInterface` boundary — atomic. This is the explicit
   improvement over nene-invoice's best-effort recording.
3. **Sanitized snapshots.** before/after reuse the API presenters; secrets, tokens,
   raw bytes, and `storage_path` are never written (`audit-logging.md` §5).
4. **Coverage.** All create/update/delete and business state transitions
   (`report.submitted/approved/rejected`, …), plus data egress (`report.exported`)
   and authentication events (`auth.login_succeeded/failed`). Event names are
   registered in `terms.md §8`.
5. **Immutable & tenant-scoped.** `audit_events` is append-only (no update/hard
   delete), carries `organization_id`, and the viewer is admin/superadmin-only.
   A `request_id` column correlates events with application logs.
6. **Retention** follows ADR 0010 (no auto-purge; warn before destruction).

## Consequences

- Uniform, complete "who changed what, before → after" trail; the field-level diff
  is reconstructable for any record.
- Use cases gain an `AuditRecorder` dependency and an `actor_id` input; mutation +
  audit are wrapped in one transaction.
- Secrets are structurally excluded by reusing sanitized presenters.
- The audit store holds PII (e.g. report `body`) under access control — this is
  distinct from debug logs, which never log such content.

## Related

- Issue: `#13`
- PR: `#000`
- Implements: requirements NF10; refines the `AuditEvent` model in `domain-model.md`
- Related: ADR 0010 (retention), ADR 0011 (UTC time), ADR 0013 (tenancy);
  reference: nene-invoice ADR 0008; binding doc `docs/development/audit-logging.md`
- Supersedes: none
- Superseded by: none
