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
- [x] Audit log read API — `/audit-events` list + `/audit-events/export` CSV (#41)

**Phase 2 の backend API は完了。残りはフロントエンド。**
### Frontend
- [x] Admin UI scaffold — React + Vite + Tailwind tokens + i18n(ja/en) + FSD + MSW tests (#43)
- [x] 認証(login) + レポート一覧（縦切り1本：auth-gate / 4状態 / locale 切替）(#43)
- [x] Report 詳細 + 承認/却下 UI（detail取得・review・添付DL）(#45)
- [x] Report 提出フォーム（mobile・下書き/提出・テンプレート選択）(#47)
- [x] Templates 管理 UI（admin・CRUD・既定・動的フィールド定義）(#49)
- [x] Users 管理 UI（admin・CRUD・ロール・自己削除防止）(#51)
- [x] Audit log viewer + CSV ダウンロード UI（フィルタ・ページング）(#53)
- [x] Export 画面（レポート CSV・期間/ユーザー/ステータス）(#55)
- [x] Settings / 組織設定 UI（名前/AI/通知）(#57)
- [ ] Storybook + Playwright e2e
- [x] Docker Compose dev environment（app/mysql/phpMyAdmin/frontend・92xx レーン）(#61)
- [x] ハイファイ全面リデザイン（ブルーティール/IBM Plex Sans JP・管理PC＋提出者モバイル＋ログイン）(#63)

## Upcoming

- Phase 3: AI Summary + Notifications (incl. org `ai_api_url` / `ai_api_key` secret handling)
- Phase 4: Ecosystem Links

See [`docs/roadmap.md`](../roadmap.md) for full phase breakdown.

Last updated: 2026-06-13
