# Dependency vulnerability gate (frontend)

Every PR runs a dependency audit as a **merge gate**. This document says what the gate is,
how an exception is granted, and what is currently excepted.

- Config: [`frontend/audit-ci.jsonc`](../../frontend/audit-ci.jsonc) (the file itself carries
  the reasoning for each entry — keep the two in sync)
- Command: `npm run audit --prefix frontend`
- CI: the `Audit (fail on high/critical)` step of `Frontend CI`

## The gate

`audit-ci` fails the build on any **high** or **critical** advisory that is not explicitly
allowlisted. Moderate and below do not fail (they are still reported).

We use `audit-ci` rather than bare `npm audit --audit-level=high` for one reason: **`npm audit`
has no way to record a reasoned exception.** Without one, the only ways past a
not-yet-fixable advisory are to lower the severity threshold or drop the step — both of which
blind the gate to *everything*, not just the advisory in question.

## Rules for an exception

1. **Per advisory id, never per severity.** Allowlist `GHSA-…`; do not raise `--audit-level`
   and do not set `high: false`. A new advisory must still fail the build the day it lands.
2. **The reason must be measured, not assumed.** State why the vulnerable code path does not
   exist *in this codebase*, and how that was checked (a grep, a build artifact, a config).
   "We probably don't use that" is not a reason.
3. **Every entry has an expiry** and a named condition that removes it (an upgrade wave, an
   upstream fix). An expired entry is a task — re-argue it in a PR; do not extend it by reflex.
4. **Prefer the fix.** If a patched version exists in a range we can take, take it. An
   exception is only for "no fix exists that we can adopt". Bump first, re-measure, and
   allowlist only what survives.

## Current exceptions

| Advisory | Package | Why it does not apply here | Expires |
| --- | --- | --- | --- |
| [GHSA-qwww-vcr4-c8h2](https://github.com/advisories/GHSA-qwww-vcr4-c8h2) | `react-router` (7.12.0–8.2.0) | The admin console is a **static SPA built by Vite** into `public_html/admin/`. `src/app/router.tsx` uses `createBrowserRouter` with element-only routes — **no RSC mode, no server components, no route `action`/`loader`, no `@react-router/dev` runtime**. The advisory's attack path (a server executing a route action before returning 400) has no counterpart in a client-only bundle. Measured 2026-07-29: 0 route-level `action:` / `loader:` keys in `src/`; all 17 react-router call sites import `react-router-dom`; 0 hits for `@react-router/dev` / `react-router/rsc` / `createStaticHandler` / `StaticRouterProvider`. | **2026-08-31** |
| [GHSA-mh99-v99m-4gvg](https://github.com/advisories/GHSA-mh99-v99m-4gvg) | `brace-expansion` (`<=5.0.7`) | **Dev-only.** `npm ls --omit=dev --all` contains zero `brace-expansion` and zero `minimatch` nodes — it is absent from the shipped `public_html/admin` bundle. The only consumers are the lint toolchain (`eslint-plugin-import`, `eslint-plugin-jsx-a11y` → `minimatch@3.1.5`) and OpenAPI codegen (`openapi-typescript` → `@redocly/openapi-core` → `minimatch@5.1.9`), and the glob patterns they expand come from our own config files — not from user input, a request, or a report payload. The DoS has no attacker-reachable path. See "why the fix is not adoptable" below. | **2026-08-31** |

**GHSA-qwww-vcr4-c8h2** — there is no fix available in the 7.x line: `react-router-dom` ends at
7.18.1, and the fix lands in `react-router` v8 (≥ 8.3.0) — a different package and a breaking
upgrade. Removed by the **react-router v8 migration wave** (bundled with the NENE2 RR8
re-evaluation).

**GHSA-mh99-v99m-4gvg** — why the fix is not adoptable. The advisory declares a single range,
`<= 5.0.7`, with `first_patched_version = 5.0.8` (confirmed against the GitHub advisory API):
**there is no patched 1.x and no patched 2.x.** But `minimatch` 3.x/5.x consume brace-expansion
through its old CommonJS default-function export, which 5.x no longer provides — a flat
`"brace-expansion": "^5.0.8"` override installs cleanly and then crashes `npm run lint` with
`TypeError: expand is not a function`. Measured, not assumed: we tried it. So the overrides pin
the newest of each major line (`1.1.16` / `^2.1.3` / `^5.0.8`) and the 1.x/2.x instances stay
inside the advisory with nowhere to go. Removed by a **toolchain wave** — eslint 10 (whose
`@eslint/config-array` 0.23.x takes `minimatch ^10` → `brace-expansion ^5.0.5`) plus
`eslint-plugin-import` / `eslint-plugin-jsx-a11y` / `@redocly/openapi-core` bumps — deliberately
not bundled into a security PR.

> ⚠️ **Fleet note:** the reference implementation uses a flat `"brace-expansion": "^5.0.8"`.
> That silently redirects any `minimatch@3.x` in the tree to an incompatible major. Ships on
> eslint 9 hit `expand is not a function` immediately; ships on eslint 10 keep the same
> mis-resolution latent behind `eslint-plugin-import` / `eslint-plugin-jsx-a11y`. Re-measure
> `npm run lint` after copying, do not assume.

## What "bump first" looked like here

Four high advisories were open against this tree when the gate was introduced (2026-07-29).
Two were closed by taking the fix; two had no adoptable fix and were allowlisted:

| Advisory | Package | Resolution |
| --- | --- | --- |
| GHSA-r28c-9q8g-f849 | `postcss` (`<=8.5.17`) | bumped to 8.5.24 |
| GHSA-chx6-hx7r-mcp5 | `react-router` (`>=7.0.0 <7.18.0`) | bumped to 7.18.1 — no v8 needed |
| GHSA-mh99-v99m-4gvg | `brace-expansion` (`<=5.0.7`) | allowlisted — no patched 1.x/2.x exists, and 5.x is API-incompatible with `minimatch` 3.x/5.x |
| GHSA-qwww-vcr4-c8h2 | `react-router` (`>=7.12.0 <8.3.0`) | allowlisted — no adoptable fix |

That is the intended shape of the rule "prefer the fix": bump first, **re-measure**, then list
only what survives — by id, with a reason and an expiry.

## Related

- [`coding-standards.md`](./coding-standards.md) — the wider merge-gate set
- Pinning an exact version to dodge an advisory is a **time-limited** measure, not a fix: the
  pinned version can itself fall inside a later advisory. The `brace-expansion@1/@2/@5` exact
  pins added on 2026-07-21 were themselves inside `GHSA-mh99-v99m-4gvg` eight days later.
  Prefer ranges, and revisit pins.
- This setup follows the fleet reference implementation (nene-contact, 施主 GO 2026-07-29).
  Both claims above were **re-measured in this tree** rather than copied, and that is why this
  repo's allowlist has two entries where the reference has one — copying an exception without
  re-measuring is the failure mode rule 2 exists to prevent.
