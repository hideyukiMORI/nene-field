# ADR 0015: Message Catalog & Runtime Locale Switching

## Status

accepted

## Context

[ADR 0012](./0012-international-readiness.md) made `en` a first-class locale and
committed NeNe Field to staying i18n-ready, with `ja` as the default. The operator
has now stated that **language switching is a premise of the service**: all
user-facing copy must be managed as message catalogs and switch cleanly at runtime.

This needs a concrete, binding structure — otherwise strings get hardcoded in
components and locales drift out of parity. The sibling **nene-invoice** already
ships a working pattern:

- per-locale catalog files (`messages/ja.ts` authoritative, `messages/en.ts`
  `Partial` with fallback to `ja`);
- **type-safe keys** (`MessageKey = keyof typeof jaMessages`) so a typo/missing key
  is a compile error;
- a context provider with `setLocale` (runtime switch, no reload) persisted to
  `localStorage`, detection `stored → navigator.language → ja`;
- API errors localized on the client by mapping the Problem Details **slug** to a
  catalog message (the API stays English, ADR 0006).

A real lesson from that sibling: it shipped `ja` 625 keys vs `en` 623 — a silent
parity gap. NeNe Field must prevent this mechanically.

## Decision

Adopt the nene-invoice i18n pattern as NeNe Field's **binding** message-catalog
architecture, documented in [`../development/i18n.md`](../development/i18n.md):

1. **One catalog file per locale** under `shared/i18n/messages/`; **`ja.ts` is the
   authoritative master**, `en.ts` is `Partial` and falls back to `ja`.
2. **Type-safe keys** (`MessageKey = keyof typeof jaMessages`); `t(key, params)`
   accepts only known keys; `{{param}}` interpolation, no fragment concatenation.
3. **Runtime switching** via `I18nProvider` `setLocale` (no reload), persisted to
   `localStorage['nene-field-locale']`; detection `stored → navigator.language → ja`.
4. **Key parity enforced in CI** — `en` must define every `ja` key — to prevent the
   silent drift seen in the sibling product.
5. **Server messages localize on the client:** the API returns English + stable
   Problem Details slugs / validation codes; the UI maps slug/code → `error.*`
   catalog keys and never displays raw API text. Every slug in `terms.md §7` must
   have a catalog message.
6. **No hardcoded user-facing strings;** user data and AI summaries are content, not
   catalog copy.

This **refines ADR 0012** (it implements the i18n-readiness commitment) and does not
change the locale scope (`ja` + `en`; a third locale still needs an ADR).

## Consequences

- Switching language is a context state change, not a reload; new screens must add
  their keys to both catalogs (enforced by parity + type checks).
- The API contract stays English and stable; localization is a client concern,
  keeping `ADR 0006` intact.
- A small i18n dependency surface in `app/providers.tsx`; tests cover parity and
  `translate()`.

## Related

- Issue: `#15`
- PR: `#000`
- Refines: ADR 0012 (international readiness); related ADR 0006 (English repo/API),
  ADR 0011 (UTC time → locale-aware display)
- Binding doc: `docs/development/i18n.md`; reference: nene-invoice `shared/i18n/`
- Supersedes: none
- Superseded by: none
