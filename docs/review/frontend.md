# Frontend Self-Review

**Binding.** Run for any frontend change. Source of truth:
[`../development/frontend-standards.md`](../development/frontend-standards.md).
Do not delete items to pass; mark `N/A` only when genuinely not applicable.

## Checklist

- [ ] **Mobile-first:** works at 375px; submission form usable on iOS 15+ Safari / Android 10+ Chrome; no desktop-only assumptions.
- [ ] **Placement:** DTOs/models/enums/mappers/query-keys/queries/mutations live in `entities/{r}/`; `fetch` only in `shared/api/client.ts`; no `schema.gen.ts` import from `.tsx`; no root `src/types`/`src/utils` dump.
- [ ] **Dependency direction:** `app → pages → features → entities → shared`; no upward import; no cross-feature import; entity/feature internals reached only via `index.ts`.
- [ ] **Data flow:** mappers run in entity hooks (not components); components receive model types + callbacks, never DTOs/`Response`; stable query keys from `query-keys.ts`; mutations invalidate keys on success.
- [ ] **State:** server data in TanStack Query; URL state in React Router `searchParams`; ephemeral in `useState`; no Redux/Zustand/Jotai without an ADR; no API response stored in `useState`.
- [ ] **Four UI states** present on every data screen: loading, empty, error (safe message + retry), success.
- [ ] **TypeScript:** strict; no `any` (use `unknown`); branded IDs for resource ids; named exports only (no default export); exhaustive `switch` on unions.
- [ ] **Styling:** no raw color/spacing/type literals or Tailwind arbitrary values in components; visual values only in `shared/ui/theme/`; features import the `shared/ui` barrel.
- [ ] **i18n:** no hardcoded user-facing strings; `ja` primary (+ optional `en`); no third locale without ADR.
- [ ] **Security:** auth token in-memory (persistence needs ADR); fail-closed (401→login, 403→forbidden); RBAC gating is UX only; no `dangerouslySetInnerHTML` without DOMPurify+Issue; `rel="noopener noreferrer"` on `target="_blank"`; no secrets (only `VITE_*`).
- [ ] **PII:** report bodies, tokens, and full Problem Details never logged in production; AI content labelled.
- [ ] **API:** client maps snake_case JSON without renaming fields; `AppError` from Problem Details; no domain logic in the client.
- [ ] **Tests:** mapper/query-key unit tests per entity; every new `use-{feature}` hook ships a colocated `*.test.tsx` against MSW; MSW shapes match OpenAPI; query by role/label.
- [ ] **a11y/perf:** WCAG 2.2 AA; focus management on route/modal; `jsx-a11y` clean; route-level code splitting; lists > 100 rows virtualized.
- [ ] `npm run check --prefix frontend` passes; no forbidden anti-pattern (`useEffect`+`fetch` for server data, prop-drilling 3+ layers, class components, UI as source of truth).
