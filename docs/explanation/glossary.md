# Glossary

Definitions of domain terms used in NeNe Field. For canonical identifier spellings,
see [`docs/terms.md`](../terms.md).

| Term | Japanese | Definition |
| --- | --- | --- |
| Daily report | 日報 | A structured record of work activities submitted by a field worker for a specific work date |
| Field worker | 現場スタッフ / 提出者 | An employee who submits daily reports; corresponds to role `submitter` |
| Approver | 承認者 | A team lead or manager authorized to approve or reject submitted reports |
| Admin | 管理者 | An organization-level administrator who manages users, templates, and settings |
| Draft | 下書き | A report saved but not yet submitted for approval |
| Submission | 提出 | The act of marking a draft report as ready for approval |
| Approval | 承認 | Manager confirmation that a submitted report is accepted |
| Rejection | 差し戻し | Manager action returning a submitted report to the submitter for revision |
| Template | テンプレート | A reusable form definition that pre-defines field labels and types for a report |
| Attachment | 添付ファイル | A file (photo, PDF) attached to a report as evidence |
| AI summary | AI 要約 | A one-line summary and keyword tags generated from report body text by an AI model |
| Audit event | 監査ログ | An immutable record of a significant mutation (submit, approve, edit, etc.) |
| Organization | 組織 / テナント | The tenant entity that groups users and reports; the unit of data isolation |
| Work date | 作業日 | The date on which the reported work was performed (may differ from submission date) |
| Project code | プロジェクトコード / 現場コード | An optional code identifying the project or site associated with a report |
| Webhook | Webhook | An HTTP callback used to send notifications to external services (e.g., Slack) |
| CSV export | CSV エクスポート | A downloadable spreadsheet-compatible file of report data for payroll or billing |
