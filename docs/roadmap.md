# Roadmap

NeNe Field — **daily report platform** on NENE2.
See [ADR 0002](./adr/0002-separate-from-sibling-products.md).

## North Star

Field workers self-host NeNe Field to submit daily reports from smartphones, while
managers approve and export work data — without payroll complexity or heavy workflows.

## Phase 0: Governance and Foundation

- [ ] Governance docs, ADR 0001–0011
- [ ] Product vision, scope contract, domain model
- [ ] Legal & compliance positioning (binding) — `docs/explanation/legal-compliance.md`
- [ ] NENE2-compliant coding standards (binding) — backend + frontend + naming + review checklists
- [ ] `docs/terms.md` — single-source terminology (identifier registry + 用語一覧 §10)
- [ ] Feature list, page list, OpenAPI skeleton
- [ ] NENE2 scaffold + `GET /health`

## Phase 1: Core Report API

- [ ] Multi-tenant + JWT + RBAC (submitter / approver / admin)
- [ ] Audit logging — before/after on all mutations
- [ ] Organization + User management endpoints
- [ ] Report template management endpoints
- [ ] Report CRUD (submit, edit draft, delete draft)
- [ ] Report submission lifecycle (draft → submitted → approved / rejected)
- [ ] File attachment upload + storage
- [ ] OpenAPI 3.1 contract — all Phase 1 endpoints
- [ ] PHPUnit + PHPStan 8 gates

## Phase 2: Manager UI + Export

- [ ] Admin UI scaffold — React + Vite + ja locale
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

See [`docs/explanation/scope-boundary.md`](./explanation/scope-boundary.md).

Last updated: 2026-06-11
