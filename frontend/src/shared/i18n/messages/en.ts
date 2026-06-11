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
