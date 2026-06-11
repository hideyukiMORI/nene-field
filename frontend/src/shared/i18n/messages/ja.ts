/**
 * Authoritative master catalog. Every message key in the app is defined here;
 * `en.ts` mirrors these keys (parity is enforced by a test). Keys are
 * dot-namespaced per i18n.md §3.
 */
export const jaMessages = {
  // ── shared chrome ────────────────────────────────────────────────
  'common.app.name': 'NeNe Field',
  'common.locale.ja': '日本語',
  'common.locale.en': 'English',
  'common.language.label': '言語',
  'common.actions.signIn': 'ログイン',
  'common.actions.signOut': 'ログアウト',
  'common.actions.retry': '再試行',
  'common.actions.back': '戻る',
  'common.actions.download': 'ダウンロード',
  'common.actions.cancel': 'キャンセル',
  'common.state.loading': '読み込み中…',
  'common.nav.reports': '日報',

  // ── auth / login ─────────────────────────────────────────────────
  'auth.login.title': 'ログイン',
  'auth.login.subtitle': '管理コンソールにサインインします。',
  'auth.login.email': 'メールアドレス',
  'auth.login.password': 'パスワード',
  'auth.login.button': 'ログイン',
  'auth.login.submitting': 'サインイン中…',
  'auth.login.sessionExpired': 'セッションの有効期限が切れました。再度ログインしてください。',
  'auth.login.invalid': 'メールアドレスまたはパスワードが正しくありません。',

  // ── reports list ─────────────────────────────────────────────────
  'report.list.title': '日報一覧',
  'report.list.subtitle': '提出された日報を確認します。',
  'report.list.empty': '日報がまだありません。',
  'report.list.error': '日報の読み込みに失敗しました。',
  'report.col.workDate': '作業日',
  'report.col.title': 'タイトル',
  'report.col.user': '担当者',
  'report.col.status': 'ステータス',
  'report.status.draft': '下書き',
  'report.status.submitted': '提出済み',
  'report.status.approved': '承認済み',
  'report.status.rejected': '差し戻し',

  // ── report detail ────────────────────────────────────────────────
  'report.detail.error': '日報の読み込みに失敗しました。',
  'report.detail.notFound': '日報が見つかりませんでした。',
  'report.field.body': '作業内容',
  'report.field.user': '担当者',
  'report.field.workDate': '作業日',
  'report.field.projectCode': '案件コード',
  'report.field.tags': 'タグ',
  'report.field.submittedAt': '提出日時',
  'report.field.approvedAt': '承認日時',
  'report.field.rejectedAt': '差し戻し日時',
  'report.field.approverComment': '承認者コメント',
  'report.field.aiSummary': 'AI要約',
  'report.attachment.title': '添付ファイル',
  'report.attachment.none': '添付ファイルはありません。',
  'report.attachment.downloadError': '添付ファイルのダウンロードに失敗しました。',

  // ── report review (approve / reject) ─────────────────────────────
  'report.review.title': '承認',
  'report.review.commentLabel': 'コメント',
  'report.review.commentPlaceholder': '差し戻し理由を入力してください',
  'report.review.approve': '承認する',
  'report.review.reject': '差し戻す',
  'report.review.commentRequired': '差し戻しにはコメントが必要です。',
  'report.review.error': '操作に失敗しました。',

  // ── server error slugs (error.{problem-slug}) ────────────────────
  'error.generic': '問題が発生しました。',
  'error.unauthorized': '認証が必要です。',
  'error.forbidden': 'この操作を行う権限がありません。',
  'error.network-error': 'ネットワークに接続できませんでした。',

  // ── validation (error.validation.{code}) ─────────────────────────
  'error.validation.required': '必須項目です。',
  'error.validation.invalid_format': '形式が正しくありません。',
  'error.validation.too_short': '短すぎます。',
  'error.validation.too_long': '長すぎます。',
} as const
