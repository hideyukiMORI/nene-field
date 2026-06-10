# ADR 0012: International Readiness (Jurisdiction-Neutral Core, `en` First-Class)

## Status

accepted

## Context

NeNe Field is positioned for Japan SMB (product-vision, scope-contract), and its
governance has been deliberately Japan-anchored: the binding
`legal-compliance.md` is built entirely on Japanese law (APPI, 労基法, 電帳法,
建設業法), notifications assume LINE/email, and timestamps display in JST (ADR 0011).

However, unlike its sibling products, **NeNe Field's core domain is not
jurisdiction-locked**. The siblings encode Japan-specific statutes —
`nene-invoice` (インボイス制度/消費税), `nene-clear` (全銀/消込), `nene-vault`
(電子帳簿保存法). NeNe Field's core workflow — submit a report → approve/reject →
export — is the same anywhere. The category has real global demand (construction
daily logs and field-service apps in the West; strong daily-report/報連相 culture
across East/Southeast Asia).

The question is **how much to commit now**. Full multilingual + multi-jurisdiction
support is a large, unvalidated investment that would dilute the Japan-rigorous
work just completed. The operator-confirmed answer is **readiness only**: keep the
door open cheaply, validate demand before investing in a market.

## Decision

### Commit now (low-regret)

1. **The core product is jurisdiction-neutral.** The domain model, API contract,
   report lifecycle, RBAC, audit, and export carry **no Japan-only assumption**.
   Anything Japan-specific lives in the Japan pack (below), not in the core.
2. **`en` is a first-class locale**, maintained alongside `ja` — not an optional
   afterthought. The architecture stays i18n-ready (see `frontend-standards.md`):
   all user-facing strings are externalized; no hardcoded copy. **`ja` remains the
   default locale for the Japan edition.** A third locale still requires an ADR.
3. **Display is locale-aware.** Instants remain stored in **UTC** (ADR 0011); the
   display timezone, date, and number formats are derived from the active locale.
   **JST is the Japan-edition default**, not a hard-coded global assumption. This
   refines ADR 0011's "display in JST" rule.
4. **Japan-specific concerns are an explicit, separable "Japan pack".** The binding
   `legal-compliance.md` (APPI / 労基法 / 電帳法 / 建設業法 positioning), Japanese
   integrations (LINE, `nene-invoice` link), and JST defaults are documented as
   **Japan-scoped** and designed to be swapped/extended per region — not woven into
   the core.

### Defer until demand is validated

- Full multilingual product surface beyond `en` (docs, marketing, support).
- Multi-jurisdiction legal positioning (GDPR, CCPA, local labor law) — an
  international edition needs its **own** per-jurisdiction positioning; the Japan
  `legal-compliance.md` does **not** transfer.
- Data-residency guarantees, multi-currency, RTL languages, locale-specific
  notification channels.

Market investment (overseas landing page, sales, localized support) is **gated on
demand validation** (e.g. interviews with East-Asian SMB / construction users),
not on this ADR.

## Consequences

- An international edition later means **extending the i18n catalog and swapping the
  Japan pack**, not re-architecting the core — the cheap option is preserved.
- The Japan edition's binding rules (`legal-compliance.md`, `terms.md`) are
  unchanged and remain fully enforced; this ADR only *scopes* them as Japan-specific.
- `frontend-standards.md` is updated to treat `en` as first-class and display as
  locale-aware.
- No new market commitment is made; the team stays focused on Japan SMB.

## Related

- Issue: `#9`
- PR: `#000`
- Refines: ADR 0011 (UTC storage / JST display → locale-aware display, JST default)
- Refined by: ADR 0015 (message catalog & runtime locale switching) + `docs/development/i18n.md`
- Related: ADR 0003 (dual-tier), ADR 0006 (English-only repo docs), ADR 0007/0009
  (AI / APPI), ADR 0008 (non-statutory positioning)
- Binding docs scoped as Japan pack: `docs/explanation/legal-compliance.md`
- Supersedes: none
- Superseded by: none
