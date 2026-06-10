# ADR 0008: Non-Statutory Record Positioning

## Status

accepted

## Context

NeNe Field stores daily reports that describe field work. Such records sit
dangerously close to several Japanese statutory systems, and an operator (or a
reviewing 社会保険労務士 / 行政書士 / 税理士) could mistakenly assume the product
*is* one of them:

- an objective working-time record under 労働安全衛生法 §66の8の3 and the
  厚生労働省「労働時間の適正な把握のために使用者が講ずべき措置に関するガイドライン」,
  or an 出勤簿 / 賃金台帳 (労基法 §108);
- a 国税関係書類 store under 電子帳簿保存法;
- a 建設業法 施工体制台帳 / 下請法 record;
- an accounting book (帳簿) source.

The scope contract already says NeNe Field does not calculate payroll or keep
statutory books (X1, X6). What was missing is a **single, binding statement of
legal positioning** that engineering, UI copy, and marketing cannot drift from.
The greatest legal risk for a lightweight tool is *overclaiming*.

## Decision

**NeNe Field is positioned as a non-statutory, supporting record system.** Its
records are work-content communication plus a tamper-evident operational log; they
are explicitly **not** any statutory record-of-truth.

Concretely:

1. The product **does not capture working time** (no clock-in/out, no start/end
   time as an hours measure). `work_date` is the calendar date the work relates
   to, not a working-time measurement.
2. The product **MUST NOT present, label, or market** reports or their CSV export
   as an 出勤簿 / 賃金台帳 / objective working-time record, a 法定帳簿, a 電帳法
   archive, a 建設業法 / 下請法 ledger, or an accounting book.
3. `docs/explanation/legal-compliance.md` is the binding source of truth for this
   positioning; `docs/review/legal-compliance.md` is its self-review checklist.
4. Moving any of these boundaries (e.g. making reports a statutory working-time
   record) is a scope change requiring a superseding ADR **with sign-off from the
   relevant licensed professional** (e.g. 社会保険労務士 for working time).

## Consequences

- Operators get an honest, defensible product; reviewing 士業 find a clear "what
  it is not" statement instead of an implicit overclaim.
- The objective working-time record-of-truth, statutory books, and document
  archives remain the operator's separate systems (and sibling products such as
  NeNe Vault / NeNe Invoice).
- UI strings, README, and operator guide are constrained by the prohibited-claims
  list (`legal-compliance.md` §10); PRs must run the legal self-review.

## Related

- Issue: `#3`
- PR: `#000`
- Binding doc: `docs/explanation/legal-compliance.md`
- Related: ADR 0002 (domain separation), ADR 0007 (AI summary), ADR 0009, ADR 0010, ADR 0011
- Supersedes: none
- Superseded by: none
