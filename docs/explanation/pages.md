# Page List

All screens (frontend pages) for NeNe Field. Phase indicates when the page ships.
Pages are React + Vite; mobile-first design throughout.

## Authentication

### P-01 Login Page — ログイン

| Attribute | Value |
| --- | --- |
| Path | `/login` |
| Phase | 2 |
| Actor | All |
| Access | Public |

- Email + password form.
- Submit → `POST /auth/login` → JWT stored in localStorage.
- Redirect to Dashboard on success.
- Error message for invalid credentials.
- Mobile-optimized (large input fields, no zoom on focus).

---

## Submitter Views

### P-02 My Reports — マイレポート

| Attribute | Value |
| --- | --- |
| Path | `/my-reports` |
| Phase | 2 |
| Actor | Submitter (and all roles) |
| Access | Authenticated |

- List of own reports, sorted by work_date desc.
- Status badges: draft / submitted / approved / rejected.
- Tap to open Report Detail.
- FAB (floating action button) → New Report.
- Pull-to-refresh on mobile.

---

### P-03 New Report — 新規日報作成

| Attribute | Value |
| --- | --- |
| Path | `/reports/new` |
| Phase | 2 |
| Actor | Submitter |
| Access | Authenticated (submitter, approver, admin) |

- **Template selector:** dropdown of organization templates; default pre-selected.
- **Work date:** date picker, defaults to today.
- **Title:** auto-generated (`YYYY-MM-DD username` pattern) or manual.
- **Body / template fields:** rendered dynamically from template definition.
- **Tags:** tag input with autocomplete from own recent tags.
- **Project code:** optional text field.
- **Attachments:** up to 5 files; camera or gallery on mobile; drag-and-drop on desktop.
- **Save draft** and **Submit** buttons (both visible).
- Unsaved changes warning on navigation.

---

### P-04 Edit Report — 日報編集

| Attribute | Value |
| --- | --- |
| Path | `/reports/{report_id}/edit` |
| Phase | 2 |
| Actor | Submitter (draft or rejected state only) |
| Access | Authenticated; own report; draft or rejected status |

- Same layout as New Report, pre-filled with existing data.
- Attachments: existing files shown with delete option; upload new.
- **Save draft** and **Submit** buttons.
- Shows approver's rejection comment at top if status is `rejected`.

---

### P-05 Report Detail — 日報詳細

| Attribute | Value |
| --- | --- |
| Path | `/reports/{report_id}` |
| Phase | 2 |
| Actor | All (own reports for submitter; all reports for approver/admin) |
| Access | Authenticated |

- Report metadata: title, submitter, work date, status, submitted_at.
- AI summary badge (if available): one-line summary + tags.
- Full body text (or template fields).
- Attachment thumbnails / file list with download links.
- Approver comment (if rejected or approved with comment).
- **For approvers:** Approve and Reject buttons (visible when status = `submitted`).
- **For submitter on rejected:** Edit button.
- Link to Invoice work order (if set) — external link.

---

## Manager / Approver Views

### P-06 Report List — 日報一覧（管理者）

| Attribute | Value |
| --- | --- |
| Path | `/reports` |
| Phase | 2 |
| Actor | Approver, Admin |
| Access | Authenticated (approver, admin) |

- Paginated list of all organization reports.
- **Filters panel:** submitter (user dropdown), work date range (from/to), tags (multi-select), status (multi-select), project code.
- **Sort:** work_date desc (default), submitted_at desc.
- Each row shows: submitter name, work_date, title, AI summary (if available), status badge, approve/reject quick action buttons.
- Bulk approve: select multiple submitted reports → approve all.
- Export CSV button → CSV export modal (P-12).

---

### P-07 Approval Action Modal — 承認・差し戻しモーダル

| Attribute | Value |
| --- | --- |
| Path | Modal on P-05 / P-06 |
| Phase | 2 |
| Actor | Approver |
| Access | Authenticated (approver, admin) |

- Approve: confirmation + optional comment → `POST /reports/{id}/approve`.
- Reject: mandatory comment field → `POST /reports/{id}/reject`.
- Shows AI summary for quick context.

---

## Admin Views

### P-08 Template Management — テンプレート管理

| Attribute | Value |
| --- | --- |
| Path | `/admin/templates` |
| Phase | 2 |
| Actor | Admin |
| Access | Authenticated (admin) |

- List of organization templates with edit/delete/toggle-default.
- Create new template button → opens Template Editor.

---

### P-09 Template Editor — テンプレートエディタ

| Attribute | Value |
| --- | --- |
| Path | `/admin/templates/new`, `/admin/templates/{id}/edit` |
| Phase | 2 |
| Actor | Admin |
| Access | Authenticated (admin) |

- Template name and description.
- Drag-and-drop field list: add field, set label, type (text / textarea / number / checkbox / date / select), required toggle.
- Preview mode: renders the form as submitter would see it.
- Save button.

---

### P-10 User Management — ユーザー管理

| Attribute | Value |
| --- | --- |
| Path | `/admin/users` |
| Phase | 2 |
| Actor | Admin |
| Access | Authenticated (admin) |

- List of organization users: name, email, role, status (active/disabled).
- Invite / create new user (name, email, role; temp password sent by email).
- Edit role.
- Disable / re-enable user.
- Delete user (with data retention warning).

---

### P-11 Audit Log — 監査ログ

| Attribute | Value |
| --- | --- |
| Path | `/admin/audit` |
| Phase | 2 |
| Actor | Admin |
| Access | Authenticated (admin) |

- Paginated audit event list, newest first.
- Each row: occurred_at, event name, actor name, entity type + id.
- Filter by event type, actor, date range.
- Click row → before/after JSON diff modal.

---

### P-12 CSV Export — CSV エクスポート

| Attribute | Value |
| --- | --- |
| Path | `/admin/export` (or modal from P-06) |
| Phase | 2 |
| Actor | Admin |
| Access | Authenticated (admin) |

- Date range picker (from / to work_date).
- User selector (all or specific).
- Project code filter.
- Status filter (approved only / all submitted+approved / all).
- Preview: row count.
- Download button → `GET /export/csv` with query params.

---

### P-13 Organization Settings — 組織設定

| Attribute | Value |
| --- | --- |
| Path | `/admin/settings` |
| Phase | 2 |
| Actor | Admin |
| Access | Authenticated (admin) |

- Organization name and contact email.
- AI summary toggle + API URL + API key (masked) → Test button.
- Notification email address.
- Webhook URL + test button.
- Save button.

---

## Dashboard

### P-14 Dashboard — ダッシュボード

| Attribute | Value |
| --- | --- |
| Path | `/` (default after login) |
| Phase | 2 |
| Actor | All |
| Access | Authenticated |

**Submitter view:**
- Today's report status (submitted / draft / not yet submitted).
- Recent report list (last 7 days).
- FAB → New Report.

**Approver / Admin view:**
- Pending approvals count and list (submitted reports awaiting action).
- Today's submission count.
- Quick filters: "Pending" / "Today" / "This week".
- Shortcut to Export.

---

## Summary

| Page | Path | Phase | Actor |
| --- | --- | --- | --- |
| P-01 Login | `/login` | 2 | All |
| P-02 My Reports | `/my-reports` | 2 | Submitter+ |
| P-03 New Report | `/reports/new` | 2 | Submitter+ |
| P-04 Edit Report | `/reports/{id}/edit` | 2 | Submitter |
| P-05 Report Detail | `/reports/{id}` | 2 | All |
| P-06 Report List | `/reports` | 2 | Approver+ |
| P-07 Approval Modal | (modal) | 2 | Approver |
| P-08 Template Management | `/admin/templates` | 2 | Admin |
| P-09 Template Editor | `/admin/templates/…` | 2 | Admin |
| P-10 User Management | `/admin/users` | 2 | Admin |
| P-11 Audit Log | `/admin/audit` | 2 | Admin |
| P-12 CSV Export | `/admin/export` | 2 | Admin |
| P-13 Organization Settings | `/admin/settings` | 2 | Admin |
| P-14 Dashboard | `/` | 2 | All |
