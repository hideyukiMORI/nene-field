# ADR 0010: Record Retention — No Auto-Purge

## Status

accepted

## Context

Reports, attachments, and audit events may be needed long after submission — as
supporting evidence for labor, tax, or dispute purposes. Japanese statutory
retention periods vary by record type and are the operator's obligation (e.g.
labor records 5 years with a transitional 3-year measure under 労基法 §109;
tax-related books commonly 7 years). NeNe Field is **not** the statutory
record-of-truth (ADR 0008), so it must not pretend to enforce these periods — but
it also must not silently destroy data the operator may be relying on.

There is a tension with APPI deletion rights (ADR 0009 §6): operators must be able
to delete personal data on request. The retention rule must accommodate explicit,
audited deletion without permitting silent purges.

## Decision

1. **No silent auto-purge.** The product MUST NOT automatically delete reports,
   attachments, or audit events based on age or any background job.
2. **Warn before destruction.** Any destructive action (bulk delete, organization
   removal, user deletion that removes reports) MUST warn the operator first and is
   recorded as an `AuditEvent`.
3. **Retention is the operator's responsibility.** The product MUST NOT *claim* to
   enforce a statutory retention period. The operator guide may list common periods
   as **guidance only**, clearly labelled as the operator's (and their 士業's)
   responsibility.
4. **Deletion is explicit and audited.** APPI-driven deletion (ADR 0009) is an
   operator-initiated action with an audit record — never a silent background purge.

## Consequences

- Operators relying on NeNe Field data as supporting evidence are protected from
  accidental data loss.
- APPI deletion requests are satisfied via an explicit, audited operator action.
- The product carries no retention-scheduler complexity and makes no statutory
  retention guarantee it cannot keep.

## Related

- Issue: `#3`
- PR: `#000`
- Binding doc: `docs/explanation/legal-compliance.md` §6
- Related: ADR 0008 (non-statutory positioning), ADR 0009 (personal data)
- Supersedes: none
- Superseded by: none
