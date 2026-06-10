# Roadmap

NeNe Field — **daily report platform** on NENE2.
See [ADR 0002](./adr/0002-separate-from-sibling-products.md).

## North Star

Field workers self-host NeNe Field to submit daily reports from smartphones, while
managers approve and export work data — without payroll complexity or heavy workflows.

## Phase 0: Governance and Foundation

- [ ] Governance docs, ADR 0001–0015
- [ ] Product vision, scope contract, domain model
- [ ] Legal & compliance positioning (binding) — `docs/explanation/legal-compliance.md`
- [ ] International-readiness stance (jurisdiction-neutral core, `en` first-class) — ADR 0012
- [ ] NENE2-compliant coding standards (binding) — backend + frontend + naming + review checklists
- [ ] `docs/terms.md` — single-source terminology (identifier registry + 用語一覧 §10)
- [ ] Feature list, page list, OpenAPI contract (v0.2.0, Phase-1-ready)
- [ ] NENE2 scaffold + `GET /health`

## Phase 1: Core Report API

- [ ] Multi-tenant resolution + isolation (ADR 0013 / `multi-tenancy.md`) + JWT + RBAC (submitter / approver / admin)
- [ ] Audit logging — sanitized before/after on all mutations, same transaction (ADR 0014 / `audit-logging.md`)
- [ ] Organization + User management endpoints
- [ ] Report template management endpoints
- [ ] Report CRUD (submit, edit draft, delete draft)
- [ ] Report submission lifecycle (draft → submitted → approved / rejected)
- [ ] File attachment upload + storage
- [ ] OpenAPI 3.1 contract — all Phase 1 endpoints
- [ ] PHPUnit + PHPStan 8 gates

## Phase 2: Manager UI + Export

- [ ] Admin UI scaffold — React + Vite + i18n message catalog (ja master + en parity, runtime switch; ADR 0015 / `i18n.md`)
- [ ] Report list with filters (staff / date / tag / approval status)
- [ ] Report detail with approval actions
- [ ] Report submission form (mobile-optimized)
- [ ] CSV export (date range, user, project)
- [ ] Audit log viewer (admin)
- [ ] Docker Compose dev environment

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

Last updated: 2026-06-11
