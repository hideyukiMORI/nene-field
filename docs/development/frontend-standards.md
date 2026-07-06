# Frontend Standards — Binding

NeNe Field's UI is a **React + TypeScript + Vite** client of the JSON API. It is
**mobile-first** (field workers submit from smartphones) and is **not** the source
of truth for schema, validation, lifecycle, or persistence — the PHP API and
`docs/openapi/openapi.yaml` own those. The UI reflects API types and errors; it
never replaces validation.

> **Status: binding.** Violations of placement, dependency direction, data flow,
> security, naming, or testing rules **block merge to `main`**. Deviations require
> an **ADR**. Status: Phase 2 — `frontend/` scaffold tracked by Issues; this
> document is the policy code follows as screens are added.

**Baseline:** NENE2 `docs/development/frontend-integration.md` (React/TS/Vite, npm,
lockfile, build output, dev proxy) and the sibling **nene-invoice**
`docs/development/frontend-standards.md`. Where this document differs, **it wins for
NeNe Field**. Self-review: [`../review/frontend.md`](../review/frontend.md).

---

## 1. Product-specific rules (read first)

| Topic | Rule |
| --- | --- |
| **Mobile-first** | Design at a **375px** baseline first; the submission form must be fully usable on iOS 15+ Safari and Android 10+ Chrome (NF1) and completable in < 3 min (NF4). Desktop is progressive enhancement. |
| **Locale** | **`ja` + `en` are both first-class, maintained locales** (ADR 0012); `ja` is the Japan-edition default. No third locale without an ADR. **No hardcoded user-facing strings — everything goes through the message catalog** (`t('key')`); runtime switch with no reload. Full rules: [`i18n.md`](./i18n.md) (binding, ADR 0015). |
| **Locale-aware display** | Instants are stored UTC (ADR 0011); **display timezone / date / number formats derive from the active locale** (JST is the Japan-edition default, not hard-coded). Never assume JST in component code. |
| **JSON shape** | API JSON is **snake_case**; the client maps it to typed models in `entities/{r}/mapper.ts` **without renaming fields in transport**. |
| **Auth token** | Bearer JWT from the login response. **In-memory session by default** (fail-closed; re-login on reload). `localStorage`/`sessionStorage` or cookie session requires an **ADR** (XSS risk). |
| **RBAC in UI** | Hide/disable actions by API-exposed role/capability (`submitter`/`approver`/`admin`). **UI gating is UX only — the API enforces authorization.** |
| **PII** | Never log report bodies, tokens, or full Problem Details in production. AI-generated content is labelled "AI summary" in the UI (ADR 0007). |
| **Build output** | Production bundle builds to **`public_html/admin/`** for Tier A same-origin hosting (ADR 0003). |

---

## 2. Stack

Adopt current stable majors at scaffold time; keep them current.

| Layer | Choice |
| --- | --- |
| UI | **React** (latest stable) — function components + hooks only, no class components |
| Language | **TypeScript** strict (`.ts`/`.tsx`) |
| Bundler | **Vite** → build to `public_html/admin/` |
| Package manager | **npm**; commit `frontend/package-lock.json`; CI uses `npm ci` |
| Node.js | active LTS (≥22); `engines` + `packageManager` in `package.json` |
| Routing | **React Router** (URL is shareable state) |
| Server state | **TanStack Query v5** |
| Forms | **React Hook Form** + **Zod** (UX validation only — API authoritative) |
| Styling | **Tailwind CSS** with semantic tokens in `shared/ui/theme/` |
| Lint/format | **ESLint** (flat, `import/no-restricted-paths`, `--max-warnings 0`) + **Prettier** |
| Test | **Vitest** + **Testing Library** + **MSW** |
| API types | **openapi-typescript** → `shared/api/schema.gen.ts` (generated; not edited) |

State management matrix — **no Redux / Zustand / Jotai without an ADR**:

| State | Tool | Location |
| --- | --- | --- |
| Remote server data | TanStack Query | `entities/*/queries.ts` |
| Writes | TanStack mutations | `entities/*/mutations.ts` |
| URL / shareable (filters, sort, page) | React Router `searchParams` | `pages/` + feature hooks |
| Form draft | React Hook Form | feature ui + hooks |
| Ephemeral UI (modal open, tab) | `useState` | feature ui |
| Auth session | Context in `app/` only | in-memory token + user |

---

## 3. Architecture (Feature-Sliced layering)

Strict layered architecture: **`app → pages → features → entities → shared`**.

| Layer | Owns | Must not own |
| --- | --- | --- |
| **`shared/`** | Transport (`api/`), design tokens (`ui/theme/`), pure utils (`lib/`), env, i18n | Routes, features, resource models, business workflows |
| **`entities/`** | One API resource: DTO mapping, query keys, TanStack hooks | JSX, cross-resource orchestration |
| **`features/`** | User workflows composing entities + UI | Raw HTTP, DTO types, raw query-key strings |
| **`pages/`** | Route wiring, lazy loading, layout slots | Business rules, API calls |
| **`app/`** | Providers, router, error boundary, auth gate | Feature-specific screens |

### Dependency direction (hard rule — no upward arrows)

```
app → pages → features → entities → shared/api → API
                      ↘ shared/ui      entities → shared/lib
```

- **Never** import `features/foo` from `features/bar`. Cross-feature sharing goes
  down to `entities/` (resource-level) or `shared/` (generic; ADR).
- `shared/` never imports `entities/`/`features/`; `entities/` never imports a
  sibling `entities/`.
- Every `entities/{r}/` and `features/{f}/` exposes **`index.ts` only**; internals
  are private. ESLint `import/no-restricted-paths` enforces this — drift is rejected.

---

## 4. Repository layout

```text
frontend/src/
  main.tsx
  app/        providers.tsx · router.tsx · root-error-boundary.tsx · auth-gate.tsx
  pages/      login/ · dashboard/ · reports/ · report-detail/ · report-submit/ · templates/ · users/ · audit-logs/ · export/ · settings/
  features/   submit-report/ · list-reports/ · approve-report/ · reject-report/ · manage-templates/ · manage-users/ · export-csv/ · view-audit-log/ …
                {feature}/ index.ts · hooks/use-{feature}.ts · ui/{Feature}.tsx · ui/{Feature}.test.tsx
  entities/   report/ · report-template/ · report-attachment/ · organization/ · user/ · audit-event/ · auth/
                {resource}/ index.ts · ids.ts · enum.ts · api-types.ts · model.ts · mapper.ts · query-keys.ts · queries.ts · mutations.ts · mapper.test.ts
  shared/
    api/      client.ts (only place fetch() lives) · errors.ts (Problem Details → AppError) · schema.gen.ts (generated)
    config/   env.ts (Zod-validated once)
    i18n/     locales.ts · translate.ts · messages/ja.ts (master) · messages/en.ts (parity) · i18n-context.tsx · use-translation.ts  (binding: i18n.md)
    lib/      pure utils
    ui/       theme/ (tokens; no React) → primitives/ → components/ → index.ts (barrel)
  tests/      setup/ · msw/ (mirror OpenAPI) · factories/ (build models) · render/ (renderWithProviders)
```

Entity folders use **kebab-case** matching the OpenAPI tag (`report`,
`report-template`, `report-attachment`, `organization`, `user`, `audit-event`,
`auth`). Built assets land in `public_html/admin/`.

### Placement matrix (zero tolerance)

| Artifact | Required path |
| --- | --- |
| OpenAPI-generated types | `shared/api/schema.gen.ts` |
| API DTOs (aliased) | `entities/{r}/api-types.ts` |
| Branded IDs | `entities/{r}/ids.ts` |
| Enums | `entities/{r}/enum.ts` |
| UI models | `entities/{r}/model.ts` |
| Mappers (pure, tested) | `entities/{r}/mapper.ts` |
| Query keys | `entities/{r}/query-keys.ts` |
| `useQuery` / `useMutation` | `entities/{r}/queries.ts` / `mutations.ts` |
| `fetch` transport | `shared/api/client.ts` **only** |
| Problem Details mapping | `shared/api/errors.ts` |
| Feature orchestration hooks | `features/{f}/hooks/` |
| Design token CSS | `shared/ui/theme/themes/*.css` only |
| UI primitives / composed | `shared/ui/primitives/` / `shared/ui/components/` |

**Forbidden (automatic reject):** DTOs/models/enums/mappers outside `entities/{r}/`;
TanStack logic outside `query-keys/queries/mutations`; `fetch` outside
`shared/api/client.ts`; `schema.gen.ts` imported from any `.tsx`; deep entity
imports from features (must go through `index.ts`); root `src/types/` or `src/utils/` dumps.

---

## 5. Data flow

### Read (server → UI)

```
API JSON → shared/api/client.ts → entities/{r}/api-types.ts (snake_case wire)
  → entities/{r}/mapper.ts (→ model) → entities/{r}/queries.ts (TanStack cache)
  → features/{f}/hooks → features/{f}/ui (render props)
```

Mappers run **inside entity hooks**, not components. Components receive **model
types + plain callbacks** — never raw `Response`, never DTOs. List screens use
**stable query keys** from `query-keys.ts` only.

### Write (UI → server)

```
UI event → features/{f}/hooks → entities/{r}/mutations.ts → shared/api/client.ts → API
  onSuccess: invalidate query-keys (explicit, colocated)
  onError:   Problem Details → AppError → UI feedback
```

### Four explicit UI states (every data screen)

**Loading** · **Empty** (intentional copy) · **Error** (safe message + retry;
Problem Details `type` logged dev-only) · **Success**.

---

## 6. HTTP client & API access

```
UI → feature hook → entity query/mutation → shared/api/client → API
```

- Single `apiClient` with typed methods; attaches the in-memory bearer token;
  **fail-closed**. Parses JSON; throws **`AppError`** from Problem Details on 4xx/5xx.
  **No domain logic — transport only.**
- TanStack hooks have explicit return types; `queryFn` runs the mapper before
  caching. Auth: 401 → login, 403 → forbidden; never a silent unauthenticated mutation.

---

## 7. TypeScript strictness

`strict` + `noUncheckedIndexedAccess`, `noImplicitOverride`,
`exactOptionalPropertyTypes`, `verbatimModuleSyntax`, `noUnusedLocals/Parameters`,
`noFallthroughCasesInSwitch`, `isolatedModules`, `noEmit`.

- **`any` forbidden** — use `unknown` and narrow. `@ts-expect-error` needs an
  Issue/ADR id. No `!` without an invariant comment.
- **Branded IDs** in `ids.ts` — no bare `string` for resource ids across layers.
- `interface` for component props; `type` for unions. Exhaustive `switch` on unions.
- **No default exports** (named exports only).

---

## 8. Styling & theming (zero tolerance)

All visual values live in **`shared/ui/theme/`**. Components never hard-code
margin, padding, color, font, background, radius, shadow, or z-index. Consume via
Tailwind **semantic utilities** (`bg-surface`, `text-primary`, `p-inline-md`) — no
arbitrary values (`p-[13px]`), no hex/rgb/px literals in `.ts`/`.tsx` outside the
theme layer. Layering: `theme/` (no React) → `primitives/` → `components/` →
`index.ts`; features import the `shared/ui` barrel only.

---

## 9. Security

The browser is a **hostile context**.

| Topic | Rule |
| --- | --- |
| Secrets | Never in repo; only public `VITE_*` in frontend env |
| Auth token | In-memory by default; persistence needs an ADR |
| XSS | No `dangerouslySetInnerHTML` without DOMPurify + Issue |
| Links | `rel="noopener noreferrer"` on `target="_blank"` |
| Redirects | Validate post-login redirect against an allowlist |
| Dependencies | `npm audit` in CI; block high/critical on `main` |
| PII | Never log report bodies, tokens, or full Problem Details in prod |
| RBAC | Hide/disable by API capability; **API enforces** |
| Fail closed | 401 → login; 403 → forbidden |

---

## 10. Testing

| Level | Tool | Required when |
| --- | --- | --- |
| Unit | Vitest | `mapper.ts`, `query-keys.ts`, pure `lib/` — every entity |
| Integration | Vitest + Testing Library + MSW | every feature PR |
| Contract | MSW vs OpenAPI | endpoint touched |

Query by role/label/accessible name; `userEvent.setup()`; wrap with
`createTestQueryClient()` (retries off); MSW shapes match OpenAPI; mock only the
API boundary; no full-page snapshots; bug fixes ship a regression test. **Every
new `use-{feature}` hook ships a colocated `*.test.tsx` against MSW** — otherwise
it blocks merge.

---

## 11. Accessibility & performance

WCAG 2.2 AA; focus management on route change and modal open/close;
`eslint-plugin-jsx-a11y` errors fail CI; form errors via `aria-describedby`.
Route-level code splitting (`React.lazy`); virtualize lists > 100 rows;
dev-only structured logging behind `import.meta.env.DEV`.

---

## 12. Commands

```bash
npm ci --prefix frontend
npm run dev --prefix frontend       # Vite dev server (5192); API proxied to PHP app
npm run codegen --prefix frontend   # regenerate shared/api/schema.gen.ts from OpenAPI
npm run check --prefix frontend     # type-check + lint + format + test
npm run build --prefix frontend     # production build → public_html/admin/
```

---

## 13. Forbidden anti-patterns (blocks merge)

`useEffect`+`fetch` for server data · `fetch` outside `shared/api/client.ts` ·
prop-drilling server data 3+ layers · storing API responses in `useState` ·
class components · **default exports** · business rules in `shared/ui` · raw query
keys in features · cross-feature imports · `any` · auth token in `localStorage`
without ADR · raw color/spacing/type literals or Tailwind arbitrary values in
components · hardcoded user-facing strings · DB or MCP access from the browser ·
treating the UI as the source of truth for validation/lifecycle.
