# Legal & Compliance Self-Review

**Binding.** Use for **any** change touching report fields, audit logging, AI
transmission, retention/deletion, attachments, CSV export, or any UI / README /
marketing / operator-guide copy. If unsure whether a change has legal impact,
assume it does and run this list.

Source of truth: [`../explanation/legal-compliance.md`](../explanation/legal-compliance.md).
Do not delete items to pass. Mark `N/A` only when genuinely not applicable.

## Checklist

- [ ] Change reviewed against `docs/explanation/legal-compliance.md`; legal/compliance impact stated in the PR.
- [ ] No prohibited claim introduced (§10): no 出勤簿/タイムカード/労働時間管理, 法定帳簿/帳簿性, 電帳法対応, 認定タイムスタンプ/非改ざん証明, 建設業法/下請法 claims.
- [ ] **Labor:** no clock-in/out or start/end **time** captured as a working-time measure; `work_date` stays a calendar date; reports/CSV not positioned as 出勤簿/賃金台帳 (ADR 0008).
- [ ] **APPI purpose & minimization:** personal data used only for report management; AI sends `body` only, no metadata, no attachments (NF15, ADR 0007).
- [ ] **APPI security:** tenant isolation on every tenanted query (NF6); storage paths never in API responses (NF7); bcrypt cost ≥ 12 (NF9).
- [ ] **Sensitive info (要配慮個人情報):** no field requires health/incident personal data; operator warned not to collect it without consent.
- [ ] **Cross-border AI (§28):** if the AI endpoint is overseas, admin UI warns and the operator's §28 obligations are documented before enabling (ADR 0009); AI stays opt-in / off by default.
- [ ] **Deletion rights:** operator can delete a user and their personal data; deletion is operator-initiated and audited (NF16, ADR 0010).
- [ ] **Integrity:** approved reports immutable (NF12); every mutation writes an `AuditEvent` before/after in the same transaction (NF10); audit log not hard-deleted; attachments SHA-256 verified (NF11).
- [ ] **Time:** instants stored in UTC, displayed in JST (ADR 0011).
- [ ] **Retention:** no silent auto-purge; destructive actions warn first; no claim of enforcing a statutory retention period (ADR 0010).
- [ ] **電帳法 boundary:** attachments not presented as a 国税関係書類 archive; receipts/invoices pointed to NeNe Vault.
- [ ] **Tax/accounting hand-off:** CSV is raw data; no 仕訳/勘定科目/消費税区分 generated; no 帳簿性 claim.
- [ ] **Industry-law boundary:** not presented as 建設業法 施工体制台帳 / 下請法 record.
- [ ] Any deviation from a binding rule, or any move of a §1 statutory boundary, carries an ADR with the relevant licensed-professional sign-off.
