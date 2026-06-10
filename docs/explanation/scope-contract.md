# Scope Contract — GOAL / DO / DON'T (binding)

**Status: binding (non-negotiable).** Charter for what NeNe Field **is**, what it
**does**, and what it **must never do**. Every Issue, ADR, and PR is measured against it.
Changing this contract requires an ADR.

Read first: [ADR 0002](../adr/0002-separate-from-sibling-products.md),
[`scope-boundary.md`](./scope-boundary.md).

---

## Governing principle

1. **Field first.** Every feature is measured against one question: does this help
   a field worker submit faster or a manager review faster?
2. **No scope creep into billing or labor law.** NeNe Field is a communication and
   record-keeping tool, not payroll or accounting software.
3. **Privacy by default.** AI features are opt-in. PII in reports is never
   unnecessarily exposed.

---

## GOAL

> **NeNe Field lets Japan SMB field workers submit daily reports from their
> smartphones and lets managers approve, track, and export work records —
> without payroll complexity or heavy enterprise workflows.**

Concretely, the goal is reached when:

1. A field worker can submit a daily report (with photo) from a smartphone in under 3 minutes.
2. A manager can approve or reject the report with a comment from their smartphone.
3. The manager can search and filter reports by staff, date, tag, and status.
4. Work data can be exported as CSV for payroll or billing systems.
5. An audit trail exists for every submission, approval, and edit.

---

## DO — NeNe Field owns these

| # | NeNe Field does | Notes |
| --- | --- | --- |
| D1 | Accept daily report submission (template + free text + photo) | Mobile-first |
| D2 | Manage report templates per organization | Admin-managed |
| D3 | Enforce report lifecycle: draft → submitted → approved / rejected | See `docs/terms.md §2` |
| D4 | Notify approvers on submission (email + optional webhook) | Webhook = Slack-compatible |
| D5 | Provide manager list view with filters | staff / date / tag / status |
| D6 | Support approval and rejection with comment | One-level only (Phase 1) |
| D7 | Store and serve file attachments (photos, PDFs) | Size-limited; compressed |
| D8 | Export report data as CSV | Date range + user + project filters |
| D9 | Record audit events for every mutation | Immutable log |
| D10 | Generate AI summary + keyword tags (opt-in per org) | See ADR 0007 |
| D11 | Manage organization users and roles | submitter / approver / admin |
| D12 | Link reports to `nene-invoice` work orders via HTTP reference (optional) | No shared DB |
| D13 | Multi-tenant isolation + JWT RBAC | See ADR 0004 |
| D14 | Tier A installer + release ZIP (shared hosting) | Same codebase as Tier B Docker |

---

## DON'T — NeNe Field must never do these

| # | NeNe Field must NOT | Why | Belongs to |
| --- | --- | --- | --- |
| X1 | Calculate overtime pay, statutory overtime, or labor law compliant records | Legal complexity; payroll domain | Dedicated payroll software |
| X2 | Issue quotes, invoices, or billing documents | Billing domain | **NeNe Invoice** |
| X3 | Reconcile bank deposits or send dunning notices | Reconciliation domain | **NeNe Clear** |
| X4 | Archive received vendor documents as SSOT | Document archive domain | **NeNe Vault** |
| X5 | Normalize bank CSV columns | Bank data domain | **NeNe Profile** |
| X6 | Post journal entries or maintain a general ledger | Accounting software | Out of scope |
| X7 | Process e-sign or legally binding contracts | E-sign domain | Separate product |
| X8 | Send report body text to AI without operator opt-in | Privacy | ADR 0007 |
| X9 | Share a database with any sibling product | Schema coupling | ADR 0002 |
| X10 | Store attachment files without size/type limits | Storage cost | See requirements |
| X11 | Present reports as an objective working-time record, 出勤簿, or 賃金台帳 | Not a statutory labor record | ADR 0008, `legal-compliance.md` §3 |
| X12 | Claim certified-timestamp / non-repudiation or 電帳法 compliance | Tamper-evident ≠ certified timestamp | `legal-compliance.md` §5, §7 |
| X13 | Make any prohibited overclaim in UI / README / marketing | Overclaim is the top legal risk | `legal-compliance.md` §10 |

---

## Boundaries that are easy to get wrong

- **Reports vs timesheets.** NeNe Field records daily activities and observations.
  It does NOT compute statutory hours, overtime rates, or paid-leave balances.
- **Approval vs multi-level workflows.** Phase 1 is one-level approval only.
  Multi-level approval (e.g., team lead → department head) is Phase 2.
- **CSV export vs accounting import.** NeNe Field exports raw work records as CSV.
  It is the operator's responsibility to import that data into accounting or payroll software.

---

## Legal positioning (binding)

The legal interpretation of GOAL / DO / DON'T — what NeNe Field **is and is not**
relative to Japanese law (labor, personal data, electronic books, industry law,
tax/accounting) — is governed by the binding document
[`legal-compliance.md`](./legal-compliance.md). Every Issue and PR that touches
report fields, audit logging, AI transmission, retention, export, or any user-facing
copy MUST run the [legal self-review](../review/legal-compliance.md).

## Related

- ADR 0002: Domain separation
- ADR 0004: Multi-tenancy and roles
- ADR 0007: AI summary policy
- ADR 0008: Non-statutory record positioning
- ADR 0009: Personal data & cross-border AI transmission
- ADR 0010: Record retention — no auto-purge
- ADR 0011: UTC storage / JST display
- `docs/explanation/legal-compliance.md` (binding)
- `docs/explanation/scope-boundary.md`
