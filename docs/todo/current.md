# Current TODO

**Status: Phase 2 — Manager UI + Export (backend API in progress)**

## Phase 1 — Core Report API ✅

- [x] Multi-tenant resolution + isolation + JWT + RBAC (#21, #23)
- [x] Audit logging — sanitized before/after, same transaction (#25)
- [x] Report CRUD + submission lifecycle (draft → submitted → approved/rejected) (#27, #29)
- [x] Organization + User management endpoints (#31)
- [x] Report template management endpoints (#33)
- [x] File attachment upload + storage (#35)
- [x] API validation boundary tests (#37)

## Phase 2 — Manager UI + Export

### Backend API
- [x] CSV export — reports (`/export/csv`, UTF-8 BOM, `report.exported` audit) (#39)
- [ ] Audit log read API — `/audit-events` list + `/audit-events/export` CSV
### Frontend (not started)
- [ ] Admin UI scaffold — React + Vite + i18n catalog (ADR 0015)
- [ ] Report list / detail+approval / submission form (mobile)
- [ ] Audit log viewer (admin)
- [ ] Docker Compose dev environment

## Upcoming

- Phase 3: AI Summary + Notifications (incl. org `ai_api_url` / `ai_api_key` secret handling)
- Phase 4: Ecosystem Links

See [`docs/roadmap.md`](../roadmap.md) for full phase breakdown.

Last updated: 2026-06-11
