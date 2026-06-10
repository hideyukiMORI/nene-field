# ADR 0011: Store Timestamps in UTC, Display in JST

## Status

accepted

## Context

The recorded date and time of a report submission, approval, rejection, and every
audit event carry **evidential weight**: they are part of what makes a NeNe Field
record trustworthy as supporting documentation (`legal-compliance.md` §5). If
timestamps depend on the host server's timezone, a deployment whose clock is not
JST would silently record and display wrong times, undermining that trust.

The product ships in two tiers (ADR 0003) — shared hosting and Docker/VPS — where
the host timezone cannot be assumed. NeNe Field is pre-implementation, so the time
model can be fixed now with no migration risk. The sibling product NeNe Invoice
adopted the same convention for its statutory issue date.

## Decision

1. **Canonical storage is UTC.** All instant fields (`created_at`, `updated_at`,
   `submitted_at`, `approved_at`, `rejected_at`, audit `occurred_at`) are stored as
   UTC instants. The process timezone is forced to UTC at bootstrap so ambient time
   functions cannot leak host-local time. The JSON API returns these UTC instants
   as-is; **UTC is the documented convention** for instant fields.
2. **The authoritative clock is the server, injectable for tests.** Use cases that
   need "now" depend on a clock abstraction (UTC); client-supplied time is never
   trusted for stored instants. This keeps time-dependent logic deterministically
   testable.
3. **Display is JST.** All user-facing output (admin frontend, CSV export) converts
   UTC → JST (Asia/Tokyo) for display.
4. **Calendar-date fields are derived in JST.** `work_date` and any "today" used by
   list filters or month buckets are computed from the JST wall clock, so the
   Japanese calendar day is correct around the UTC-midnight boundary.

## Consequences

- Submission/approval/audit timestamps are correct and consistent regardless of
  host timezone; the rule is explicit in code, not dependent on host config.
- Time-dependent use cases are deterministically testable via a fixed-instant clock.
- API instant fields are UTC; the frontend and CSV layer convert to JST for display.

## Related

- Issue: `#3`
- PR: `#000`
- Binding doc: `docs/explanation/legal-compliance.md` §5
- Related: ADR 0003 (dual-tier deployment), ADR 0008 (non-statutory positioning); mirrors `nene-invoice` ADR 0010
- Refined by: ADR 0012 (display generalized to locale-aware; JST is the Japan-edition default)
- Supersedes: none
- Superseded by: none
