# Current TODO

**Status: Phase 1 — Core Report API**

## Phase 1 — Core Report API

- [x] Multi-tenant resolution + isolation + JWT + RBAC (#21, #23)
- [x] Audit logging — sanitized before/after, same transaction (#25)
- [x] Report CRUD + submission lifecycle (draft → submitted → approved/rejected) (#27, #29)
- [x] Organization + User management endpoints (#31)
- [x] Report template management endpoints (#33)
- [x] File attachment upload + storage (#35)

**Phase 1 のエンドポイント実装は完了。次は Phase 2（Manager UI + Export）。**

## Upcoming

- Phase 2: Manager UI + Export
- Phase 3: AI Summary + Notifications (incl. org `ai_api_url` / `ai_api_key` secret handling)
- Phase 4: Ecosystem Links

See [`docs/roadmap.md`](../roadmap.md) for full phase breakdown.

Last updated: 2026-06-11
