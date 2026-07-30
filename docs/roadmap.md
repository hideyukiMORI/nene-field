# Roadmap

NeNe Field — **daily report platform** on NENE2.
See [ADR 0002](./adr/0002-separate-from-sibling-products.md).

## North Star

Field workers self-host NeNe Field to submit daily reports from smartphones, while
managers approve and export work data — without payroll complexity or heavy workflows.

## Where this is, as of 2026-07-30

**Phase 0, Phase 1, and Phase 2 are complete. Phase 3 has not started.**

Measured at commit `1170aa6`:

| | Measured |
| --- | --- |
| Backend | 11,629 lines of PHP across `src/`, 6 migrations, PHPStan level 8 |
| API contract | OpenAPI 3.1.0 (`v0.2.0`), **35** operations |
| Backend tests | **201** test methods (`composer test`) |
| Frontend | **89** `.tsx` files, **28** test files / **89** unit tests passing, **9** Storybook stories |
| End-to-end | **4** Playwright specs, run in CI on every pull request |
| CI gates (required, admins not exempt) | `backend-check`, `frontend-check`, `frontend-e2e` |

These are point-in-time measurements, not a statement about coverage. Checked items below name the
issue or pull request that delivered them, so each claim can be verified against history.

## Phase 0: Governance and Foundation ✅

- [x] Governance docs, ADR 0001–0015
- [x] Product vision, scope contract, domain model
- [x] Legal & compliance positioning (binding) — `docs/explanation/legal-compliance.md`
- [x] International-readiness stance (jurisdiction-neutral core, `en` first-class) — ADR 0012
- [x] NENE2-compliant coding standards (binding) — backend + frontend + naming + review checklists
- [x] `docs/terms.md` — single-source terminology (identifier registry + 用語一覧 §10)
- [x] Feature list, page list, OpenAPI contract (v0.2.0, Phase-1-ready)
- [x] NENE2 scaffold + `GET /health` (#19)

## Phase 1: Core Report API ✅

- [x] Multi-tenant resolution + isolation (ADR 0013 / `multi-tenancy.md`) + JWT + RBAC (submitter / approver / admin) (#21, #23)
- [x] Audit logging — sanitized before/after on all mutations, same transaction (ADR 0014 / `audit-logging.md`) (#25)
- [x] Organization + User management endpoints (#31)
- [x] Report template management endpoints (#33)
- [x] Report CRUD (submit, edit draft, delete draft) (#27)
- [x] Report submission lifecycle (draft → submitted → approved / rejected) (#29)
- [x] File attachment upload + storage (#35)
- [x] OpenAPI 3.1 contract — all Phase 1 endpoints (`docs/openapi/openapi.yaml`, 3.1.0 / v0.2.0, 35 operations)
- [x] PHPUnit + PHPStan 8 gates — `composer test` / `composer analyse` at level 8, plus request-validation boundary tests (#37)

## Phase 2: Manager UI + Export ✅

- [x] Admin UI scaffold — React + Vite + i18n message catalog (ja master + en parity, runtime switch; ADR 0015 / `i18n.md`) (#43)
- [x] Report list with filters (submitter / work date range / approval status / project code) (#29 API, #43 UI)
- [x] Report detail with approval actions (#45)
- [x] Report submission form (mobile-optimized) (#47)
- [x] Report template management UI (admin — CRUD, default template, dynamic field definitions) (#49)
- [x] User management UI (admin — CRUD, roles, self-deletion guard) (#51)
- [x] Audit log viewer (admin) with CSV download (#41, #53)
- [x] CSV export — reports (work date range, submitter, status, project code; UTF-8 BOM) (#39, #55)
- [x] Organization settings UI (name, AI, notifications) (#57)
- [x] Docker Compose dev environment (#61)
- [x] High-fidelity redesign — admin desktop, submitter mobile, sign-in (#63)
- [x] Storybook + Playwright end-to-end, run in CI on every pull request (#59)

**Deferred out of Phase 2**, recorded here rather than left silently missing:

- [ ] Report list — **tag filter**. The OpenAPI contract publishes a `tags` query parameter and
      `docs/explanation/features.md` / `pages.md` describe the filter, but the server does not
      implement it: `ReportFilter` has no `tags` field and `ListReportsHandler::filterFrom()` never
      reads the parameter, so it is silently ignored. Tracked in #142 — the contract and the docs
      are wider than the implementation, and closing that gap comes first. Tags themselves are
      stored, displayed, and exported; only filtering by them is missing.

## Phase 3: AI Summary + Notifications

- [ ] AI summary — long text → one-line + keyword tags (OpenAI / compatible)
- [ ] Email notifications (submitted, approved, rejected)
- [ ] Webhook notifications (Slack-compatible)
- [ ] Weekly / monthly summary report (CSV + auto-send)

## Phase 4: Ecosystem Links

- [ ] Optional HTTP link to `nene-invoice` work orders
- [ ] Optional HTTP link to `nene-records` entities
- [ ] MCP read tools (`searchReports`, `getReportById`, `listAuditEvents`)
- [ ] Tier A shared hosting installer + release ZIP

## Not on this roadmap

- Payroll calculation or statutory labor management (残業精算, 法定帳簿)
- Bank CSV / reconciliation / dunning
- Invoice issuance or PDF generation
- Received-document archiving (that is `nene-vault`)
- E-sign or legal contract management
- Full multilingual / multi-jurisdiction support (GDPR, local labor law, multi-currency, RTL) — **deferred until overseas demand is validated**; the door is kept open cheaply via ADR 0012 (jurisdiction-neutral core + `en` first-class), not invested in here.

See [`docs/explanation/scope-boundary.md`](./explanation/scope-boundary.md).

Last updated: 2026-07-30
