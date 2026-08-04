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

**GHSA-qwww-vcr4-c8h2** — there is no fix available in the 7.x line: `react-router-dom` ends at
7.18.1, and the fix lands in `react-router` v8 (≥ 8.3.0) — a different package and a breaking
upgrade. Removed by the **react-router v8 migration wave** (bundled with the NENE2 RR8
re-evaluation).

**GHSA-mh99-v99m-4gvg was removed on 2026-08-04, not renewed** (#148). Its whole argument rested
on one measured claim — "there is no patched 1.x and no patched 2.x" — and upstream published
`1.1.18` and `2.1.4` after it was written. The moment that happened the exception had no premise
left, so the fix was taken instead. This is rule 4 working as intended, and it is the reason
rule 2 asks for the *measurement* rather than the conclusion: a conclusion cannot tell you when
it stops being true. `audit-ci` said so itself once the versions were in place —
`Consider not allowlisting advisory: GHSA-mh99-v99m-4gvg`.

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
| GHSA-mh99-v99m-4gvg | `brace-expansion` (`<=5.0.7`) | allowlisted — then **superseded by the fix on 2026-08-04**, see below |
| GHSA-qwww-vcr4-c8h2 | `react-router` (`>=7.12.0 <8.3.0`) | allowlisted — no adoptable fix |

That is the intended shape of the rule "prefer the fix": bump first, **re-measure**, then list
only what survives — by id, with a reason and an expiry.

## 2026-08-04 — an exception expired early (#148)

Two new high advisories opened against this tree (fleet #229): `GHSA-rgw5-rvv9-x895`
(`brace-expansion`, widened to `<=1.1.17` / `<=2.1.3` / `<=5.0.8`) and `GHSA-7p8r-x3mc-p8w7`
(`fast-uri`, `<=3.1.4`). Both were closed by taking the fix. **No allowlist entry was added, and
one was removed.**

| Advisory | Package | Resolution |
| --- | --- | --- |
| GHSA-rgw5-rvv9-x895 | `brace-expansion` | overrides bumped per major line: `^1.1.18` / `^2.1.4` / `^5.0.9` |
| GHSA-7p8r-x3mc-p8w7 | `fast-uri` | 3.1.4 → **3.1.5** (`stylelint` → `table` → `ajv`; inside ajv's range, so a lockfile update sufficed — no override needed) |
| GHSA-mh99-v99m-4gvg | `brace-expansion` | **allowlist entry deleted** — the same bumps carry every line past it |

Two things this tree got right, and one it got wrong:

- **Right — per-major pinning survived contact with the fix.** The 2026-07-29 finding still holds:
  `minimatch` 3.x/5.x consume brace-expansion's old CommonJS default-function export, so a *flat*
  `"brace-expansion": "^5"` still breaks `npm run lint` with `TypeError: expand is not a function`.
  Taking the fix meant bumping **each major line to its own patch**, not collapsing them.
  `npm run lint` was re-measured after the bump rather than assumed.
- **Right — the exception carried its own kill switch.** It recorded the measurement that made it
  true ("no patched 1.x, no patched 2.x — checked against the advisory API"), so when upstream
  published `1.1.18` and `2.1.4` the entry could be *checked* instead of re-argued. `audit-ci`
  reached the same conclusion unprompted: `Consider not allowlisting advisory: GHSA-mh99-v99m-4gvg`.
  This is why rule 2 asks for the measurement and not the conclusion — a conclusion cannot tell
  you when it has stopped being true.
- **Wrong — `brace-expansion@1` was an exact pin (`1.1.16`), not a range.** `^2.1.3` and `^5.0.8`
  would have moved on a plain lockfile update; the exact pin could not, and needed a
  `package.json` edit to clear. **An exception that names a specific version has an expiry whether
  or not one is written down.** Prefer ranges; an exact pin is a decision to be paged about later.

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
