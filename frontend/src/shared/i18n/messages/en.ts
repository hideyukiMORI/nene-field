import type { MessageCatalog } from '../translate'

/**
 * English catalog. Parity with the `ja` master is enforced in a test; any missing
 * key falls back to `ja` at runtime (never to a blank).
 */
export const enMessages: MessageCatalog = {
  // ── shared chrome ────────────────────────────────────────────────
  'common.app.name': 'NeNe Field',
  'common.locale.ja': '日本語',
  'common.locale.en': 'English',
  'common.language.label': 'Language',
  'common.actions.signIn': 'Sign in',
  'common.actions.signOut': 'Sign out',
  'common.actions.retry': 'Retry',
  'common.actions.back': 'Back',
  'common.actions.download': 'Download',
  'common.actions.cancel': 'Cancel',
  'common.state.loading': 'Loading…',
  'common.nav.reports': 'Reports',

  // ── auth / login ─────────────────────────────────────────────────
  'auth.login.title': 'Sign in',
  'auth.login.subtitle': 'Sign in to the admin console.',
  'auth.login.email': 'Email',
  'auth.login.password': 'Password',
  'auth.login.button': 'Sign in',
  'auth.login.submitting': 'Signing in…',
  'auth.login.sessionExpired': 'Your session expired. Please sign in again.',
  'auth.login.invalid': 'Incorrect email or password.',

  // ── reports list ─────────────────────────────────────────────────
  'report.list.title': 'Reports',
  'report.list.subtitle': 'Review submitted daily reports.',
  'report.list.empty': 'No reports yet.',
  'report.list.error': 'Failed to load reports.',
  'report.col.workDate': 'Work date',
  'report.col.title': 'Title',
  'report.col.user': 'Author',
  'report.col.status': 'Status',
  'report.status.draft': 'Draft',
  'report.status.submitted': 'Submitted',
  'report.status.approved': 'Approved',
  'report.status.rejected': 'Rejected',

  // ── report detail ────────────────────────────────────────────────
  'report.detail.error': 'Failed to load the report.',
  'report.detail.notFound': 'The report was not found.',
  'report.field.body': 'Work details',
  'report.field.user': 'Author',
  'report.field.workDate': 'Work date',
  'report.field.projectCode': 'Project code',
  'report.field.tags': 'Tags',
  'report.field.submittedAt': 'Submitted at',
  'report.field.approvedAt': 'Approved at',
  'report.field.rejectedAt': 'Rejected at',
  'report.field.approverComment': 'Reviewer comment',
  'report.field.aiSummary': 'AI summary',
  'report.attachment.title': 'Attachments',
  'report.attachment.none': 'No attachments.',
  'report.attachment.downloadError': 'Failed to download the attachment.',

  // ── report submit (create + submit) ──────────────────────────────
  'report.submit.newAction': 'New report',
  'report.submit.title': 'New report',
  'report.submit.subtitle': 'Enter the work details and submit.',
  'report.submit.fieldTitle': 'Title',
  'report.submit.workDate': 'Work date',
  'report.submit.body': 'Work details',
  'report.submit.projectCode': 'Project code (optional)',
  'report.submit.tags': 'Tags (optional)',
  'report.submit.tagsHint': 'Comma-separated',
  'report.submit.template': 'Template (optional)',
  'report.submit.templateNone': 'None',
  'report.submit.saveDraft': 'Save draft',
  'report.submit.submit': 'Submit',
  'report.submit.saving': 'Saving…',
  'report.submit.error': 'Failed to save.',

  // ── report review (approve / reject) ─────────────────────────────
  'report.review.title': 'Review',
  'report.review.commentLabel': 'Comment',
  'report.review.commentPlaceholder': 'Enter a reason for sending back',
  'report.review.approve': 'Approve',
  'report.review.reject': 'Send back',
  'report.review.commentRequired': 'A comment is required to send back.',
  'report.review.error': 'The action failed.',

  // ── server error slugs ───────────────────────────────────────────
  'error.generic': 'Something went wrong.',
  'error.unauthorized': 'Authentication is required.',
  'error.forbidden': 'You are not allowed to perform this action.',
  'error.network-error': 'Could not reach the network.',

  // ── validation ───────────────────────────────────────────────────
  'error.validation.required': 'This field is required.',
  'error.validation.invalid_format': 'The format is invalid.',
  'error.validation.too_short': 'Too short.',
  'error.validation.too_long': 'Too long.',
}
