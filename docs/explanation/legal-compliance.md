# Legal & Compliance Positioning — Binding Rules

**Status: binding (non-negotiable).** This document is the single source of truth
for **what NeNe Field is, legally, and — just as importantly — what it is NOT.**
A licensed professional (社会保険労務士 / 弁護士 / 行政書士 / 税理士・公認会計士)
reviewing the system must be able to find an honest, unambiguous statement of its
boundaries here, with **zero overclaim**.

These are not guidelines. They are **MUST** requirements. Where a rule here
conflicts with UX, marketing appeal, performance, or implementation convenience,
**the legal positioning wins** — every time, without exception.

> **This document is engineering's binding interpretation of how the product is
> positioned relative to Japanese law. It is not legal advice, and the product is
> not a substitute for advice from a licensed professional.**

> **Jurisdiction scope — this is the "Japan pack" (ADR 0012).** Every rule here is
> scoped to **Japanese law** and to the **Japan edition** of NeNe Field. The core
> product is jurisdiction-neutral; this legal positioning is a separable Japan
> layer. It does **not** transfer to other jurisdictions: an international edition
> needs its **own** per-jurisdiction positioning (e.g. GDPR/CCPA, local labor law)
> in a separate document. Do not weaken or generalize these Japan rules to "cover"
> another country — scope a new pack instead.

See also: [`scope-contract.md`](./scope-contract.md),
[ADR 0008](../adr/0008-non-statutory-record-positioning.md),
[ADR 0009](../adr/0009-personal-data-and-cross-border-ai.md),
[ADR 0010](../adr/0010-record-retention-no-auto-purge.md),
[ADR 0011](../adr/0011-utc-storage-jst-display.md), self-review checklist
[`../review/legal-compliance.md`](../review/legal-compliance.md).

---

## 0. Governing principle

1. **Honesty over claims.** The single greatest legal risk for a lightweight tool
   is *overclaiming*. NeNe Field's compliance posture is to state plainly what it
   does not do, so that no operator is misled into relying on it as a statutory
   system. Underclaiming is safe; overclaiming is a defect.
2. **Engineering is not the legal authority.** This document is engineering's
   interpretation. When a requirement is unclear or a law changes, **stop and
   consult the relevant 士業** — do not guess. Record the resolved interpretation
   here.
3. **No silent deviation.** Any departure from the rules below — even temporary,
   even in UI copy — requires an **ADR** and, where the deviation touches a
   statutory boundary, **explicit review sign-off by the relevant licensed
   professional** recorded in that ADR. No code or doc may merge a deviation
   without it.
4. **Single source of truth for positioning.** What the system *is* and *is not*
   is defined here. Marketing copy, UI strings, the README, and the operator
   guide MUST NOT contradict this document. See §10 for prohibited claims.

---

## 1. Statutory & professional landscape

NeNe Field touches the following areas. The table separates **obligations the
product itself must meet** from **boundaries the product deliberately does not
cross**. This list states *how we position ourselves*; it is not legal advice.

| Area | Rule set | Product stance |
| --- | --- | --- |
| Personal data | 個人情報保護法 (APPI) | **In scope — the product must comply** (§4) |
| Cross-border data transfer | 個人情報保護法 §28 | **In scope — warn & inform** (§4.4, ADR 0009) |
| Working-time management | 労働安全衛生法 §66の8の3・労基法 §108/§109・労働時間適正把握ガイドライン | **Boundary — not a statutory working-time record** (§3, ADR 0008) |
| Electronic books / records | 電子帳簿保存法 | **Boundary — not a 国税関係書類 archive** (§7) |
| Construction / subcontract law | 建設業法・下請法 | **Boundary — not a statutory ledger** (§8) |
| Tax & accounting books | 法人税法・所得税法・消費税法 (帳簿) | **Boundary — exports are raw data, not 帳簿** (§9) |
| Electronic document integrity | e-文書法・時刻認証 (タイムスタンプ) | **Boundary — tamper-evident, not certified timestamp** (§5) |

When any in-scope rule changes (APPI amendments, cross-border rules), treat
non-conformance as a compliance defect and open a P0 Issue. When a boundary law
changes, re-verify that our "we do not do this" statements are still accurate.

---

## 2. What a NeNe Field record IS / IS NOT

This is the core of the document. Every other section elaborates it.

### A NeNe Field report IS

- A **work-content record and communication artifact** — what was done, observed,
  or reported on a given work date, submitted by a worker and reviewed by a manager.
- A **tamper-evident operational record** — backed by an immutable audit trail
  (§5) so the operator can trust that an approved report has not been silently
  altered.
- **Exportable supporting data** — CSV the operator may feed into their own
  payroll, billing, or accounting process, where their own systems and 士業 remain
  responsible for statutory treatment.

### A NeNe Field report IS NOT

| It is NOT | Why | Owner of the real obligation |
| --- | --- | --- |
| An 出勤簿 / タイムカード / objective working-time record | Reports are self-reported work content, not an objective clock-in/out under 労安衛法 | Operator's attendance/timekeeping system + 社労士 |
| A 賃金台帳 or any 法定帳簿 | The product performs no wage/hour calculation and keeps no statutory book | Payroll software + 社労士 / 税理士 |
| A 国税関係書類 (帳簿・領収書・請求書) archive under 電子帳簿保存法 | Attachments are work-evidence photos, not a 電帳法-compliant store | **NeNe Vault** + 税理士 |
| A 建設業法 施工体制台帳 / 施工体系図 or 下請法 record | No statutory ledger structure or retention is enforced | Operator + 行政書士 / 社労士 |
| An 会計帳簿 / 仕訳 / 消費税区分 source | Exports are raw operational data with no accounting treatment | Accounting software + 税理士・公認会計士 |
| A certified-timestamp (時刻認証) non-repudiation system | The audit trail is tamper-evident, not a 認定タイムスタンプ | Dedicated timestamp authority |
| An invoice / quote issuer | Billing is a separate domain | **NeNe Invoice** |
| An e-sign / legally binding contract tool | Out of domain | Separate e-sign product |

---

## 3. Labor — working time (社会保険労務士)

**Decision: NeNe Field is not a statutory working-time record.** See ADR 0008.

- The product **MUST NOT** capture clock-in/clock-out or start/end **time** as a
  field intended to measure working hours. The report's `work_date` is the
  **calendar date the work relates to** — not a working-time measurement.
- The product **MUST NOT** present, label, or market reports or their CSV export
  as satisfying the employer's duty to objectively grasp working hours
  (労働安全衛生法 §66の8の3 and the 労働時間の適正な把握のために使用者が講ずべき
  措置に関するガイドライン), nor as an 出勤簿 or 賃金台帳 (労基法 §108).
- The objective working-time record-of-truth remains the operator's separate
  attendance/timekeeping system. NeNe Field reports may at most be **supporting
  context**, never the statutory record.
- CSV export is **raw operational data** handed to the operator's own payroll/labor
  process. The product computes no overtime, no statutory hours, no leave balances
  (scope-contract X1).

> If a future Issue proposes capturing working hours as a statutory record, it is
> a scope change: it requires an ADR superseding 0008 **with 社会保険労務士 sign-off**.

---

## 4. Personal data — 個人情報保護法 (APPI)

Reports contain personal information: worker names, locations, client names, and
free-text that may describe third parties. The product **MUST** comply with APPI.

### 4.1 Purpose limitation & minimization
- Personal data is processed only for the stated purpose (field-report management
  and review). The product MUST NOT repurpose it (利用目的の制限, APPI §17–18).
- AI summary sends **only the report `body` text** — no user name, date, or
  project metadata (requirements NF15, ADR 0007). Attachments are never sent.

### 4.2 Security control measures (安全管理措置, APPI §23)
- Tenant isolation (`organization_id` on every tenanted query — NF6) and JWT RBAC
  (ADR 0004) enforce access control.
- File storage paths are never exposed in API responses (NF7); files are served
  only via authenticated endpoints.
- Passwords are bcrypt-hashed, cost ≥ 12 (NF9).

### 4.3 Sensitive personal information (要配慮個人情報, APPI §2-3)
- Safety/incident reports may contain health or injury information, which is
  要配慮個人情報 requiring consent to acquire.
- The product **MUST NOT** require sensitive personal information in any field, and
  **MUST** warn operators (in the operator guide and template-design UI) not to
  collect it without a lawful basis and consent.

### 4.4 Outsourcing & cross-border transfer (委託・越境移転, APPI §25/§28)
- Sending report text to an external AI API is treated as outsourcing the handling
  of personal data: the operator is responsible for supervising the processor
  (委託先の監督, §25).
- **When the configured AI endpoint is outside Japan** (e.g. a US-hosted
  OpenAI-compatible API), this is a cross-border transfer (§28). The product
  **MUST** surface a clear warning in the admin settings UI before AI is enabled
  and document the operator's §28 obligations (consent or an adequate-system basis,
  plus the duty to provide information to the data subject). See ADR 0009.
- AI remains **opt-in and disabled by default** (ADR 0007). An organization that
  leaves AI off exposes no report text to any external API.

### 4.5 Data subject rights & deletion
- The product MUST let an operator delete a user and their associated personal data
  on request (requirements NF16), so the operator can answer disclosure/correction/
  cessation requests (開示・訂正・利用停止, APPI §32–35).
- Deletion of personal data and the **no-auto-purge retention rule** (§6) are
  reconciled by ADR 0010: deletion is an operator-initiated, audited action; the
  product never silently purges.
- The `.env` and operator guide MUST document which fields are PII (NF17).

---

## 5. Record integrity & evidential value (弁護士・個人情報・証跡)

- **Approved reports are immutable** (requirements NF12). A `submitted` report
  cannot be edited; it must be rejected back to `draft` first (domain-model
  lifecycle).
- **Every significant mutation writes an `AuditEvent`** with before/after state, in
  the **same DB transaction** as the mutation (NF10). Audit events are an immutable
  log; the product MUST NOT hard-delete or silently rewrite them.
- **Attachments are SHA-256 verified** (NF11), giving file-integrity evidence.
- **Tamper-evident, NOT certified timestamp.** The audit trail lets an operator
  detect alteration. It is **not** a 認定タイムスタンプ / 時刻認証業務 and the
  product MUST NOT claim non-repudiation or 電帳法-grade timestamping (§10).
- **Time is stored in UTC and displayed in JST** (ADR 0011) so the recorded
  date/time of submission, approval, and audit events is correct and consistent
  regardless of host timezone — important for the evidential value of the record.

---

## 6. Retention (保存期間)

**Decision: the product does not auto-purge and warns before destruction.** See
ADR 0010.

- Retention periods are the **operator's and their 士業's responsibility**. The
  product MUST NOT silently delete reports, attachments, or audit events.
- Any destructive retention action (bulk delete, organization removal) MUST warn
  the operator first.
- The product MUST NOT *claim* to enforce a statutory retention period. As guidance
  only, the operator guide may note common Japanese periods (e.g. labor records are
  retained 5 years, with a transitional 3-year measure under 労基法 §109; tax-related
  books are commonly 7 years) — clearly labelled as the **operator's** obligation,
  not a product guarantee.

---

## 7. Electronic books boundary — 電子帳簿保存法 (税理士・公認会計士)

- 電子帳簿保存法 governs 国税関係帳簿・書類 and 電子取引データ, with search,
  timestamp, and correction-history requirements.
- NeNe Field attachments are **work-evidence photos for reporting**, not a
  国税関係書類 store. The product MUST NOT claim to satisfy 電帳法 search/timestamp
  requirements.
- If an attachment happens to be a 領収書 / 請求書 or other 国税関係書類, archiving
  it in a 電帳法-compliant manner is the operator's responsibility — point them to
  **NeNe Vault** (received-document archive) rather than treating NeNe Field as the
  source of truth.

---

## 8. Industry-law boundary — 建設業法 / 下請法 (行政書士・業法)

- Construction operators have statutory record duties: 施工体制台帳 (建設業法
  §24の8), 施工体系図, and 帳簿の備付け (§40の3); subcontracting has document-delivery
  duties under 下請法.
- NeNe Field is **not** any of these statutory ledgers. A daily report does not
  satisfy 建設業法 / 下請法 record obligations, and the product MUST NOT imply it
  does. Those remain the operator's separate obligation (with 行政書士 / 社労士).

---

## 9. Tax & accounting hand-off (税理士・公認会計士)

- CSV export is **raw operational data**. It contains no 会計帳簿, 仕訳,
  勘定科目, or 消費税区分, and the product generates none.
- The product MUST NOT claim 帳簿性 (that the export is or substitutes for an
  accounting book). Accounting and tax treatment is performed separately by the
  operator and their 税理士・公認会計士.
- Billable-hour linkage to **NeNe Invoice** is an optional read-only HTTP
  reference (`invoice_work_order_id`); NeNe Field never issues invoices and never
  writes to billing systems (scope-contract X2, D12).

---

## 10. Prohibited claims (UI, README, marketing, operator guide)

The following claims are **prohibited** anywhere in the product or its docs,
because they would mislead an operator into relying on NeNe Field as a statutory
system:

- "出勤簿として使える" / "労働時間を法的に管理" / "タイムカードの代わり"
- "賃金台帳" / "法定帳簿" / any claim of 帳簿性 for the CSV export
- "電子帳簿保存法対応" / "電帳法対応の保存"
- "タイムスタンプで非改ざんを証明" / "認定タイムスタンプ" / non-repudiation claims
- "建設業法の施工体制台帳" / "下請法対応"
- "法令対応済み" stated without qualifying *which* obligation and whose responsibility

Permitted, honest framings include: "作業内容の記録・連絡", "改ざんを検知できる
監査ログ (tamper-evident)", "CSVで給与・請求・会計システムへ受け渡し", "AI要約は
オプトイン".

---

## 11. How this rule applies to every change

Any change that touches report fields, audit logging, AI transmission, retention,
attachments, export, or any UI/README/marketing copy MUST:

1. Be reviewed against this document and
   [`../review/legal-compliance.md`](../review/legal-compliance.md).
2. State its legal/compliance impact in the PR.
3. Add no prohibited claim (§10).
4. If it deviates from any rule here, or moves a §1 boundary, carry an ADR with the
   relevant licensed-professional sign-off (§0.3). No exceptions.

If you are unsure whether a change has legal impact, **assume it does** and run the
checklist.
