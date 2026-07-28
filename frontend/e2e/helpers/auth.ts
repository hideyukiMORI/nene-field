import { expect, type Page } from '@playwright/test'

/** JSON helper for `page.route` fulfilments. */
export function json(body: unknown, status = 200) {
  return { status, contentType: 'application/json', body: JSON.stringify(body) }
}

/** RFC 9457 problem+json helper for error fulfilments. */
export function problem(slug: string, status: number) {
  return {
    status,
    contentType: 'application/problem+json',
    body: JSON.stringify({
      type: `https://nene-field.dev/problems/${slug}`,
      title: 'Error',
      status,
    }),
  }
}

export const ADMIN_USER = {
  user_id: 'u-1',
  organization_id: 'org-1',
  name: '管理者',
  email: 'admin@example.com',
  role: 'admin',
  is_active: true,
  created_at: '2026-06-01 00:00:00',
  updated_at: '2026-06-01 00:00:00',
}

export const SUBMITTER_USER = {
  user_id: 'u-1',
  organization_id: 'org-1',
  name: '田中太郎',
  email: 'tanaka@example.com',
  role: 'submitter',
  is_active: true,
  created_at: '2026-06-01 00:00:00',
  updated_at: '2026-06-01 00:00:00',
}

export const REPORT_LIST = {
  items: [
    {
      report_id: 'r-1',
      user_id: 'u-1',
      user_name: '田中太郎',
      title: '現場A 報告',
      work_date: '2026-06-11',
      status: 'submitted',
      created_at: '2026-06-11 08:00:00',
    },
  ],
  limit: 20,
  offset: 0,
  total: 1,
}

export const ORGANIZATION = {
  organization_id: 'org-1',
  name: 'ネネ建設',
  slug: 'nene',
  custom_domain: null,
  is_active: true,
  ai_summary_enabled: false,
  notification_email: null,
  webhook_url: null,
  created_at: '2026-06-01 00:00:00',
  updated_at: '2026-06-01 00:00:00',
}

/**
 * Signs in through the real form as a manager, stubbing every request the admin
 * console makes on the way in (`/auth/login`, the reports list, the organization
 * the shell reads). Nothing may reach a real backend: an unstubbed route falls
 * through the Vite proxy to localhost:9200 and makes the suite depend on whether
 * the API container happens to be up.
 *
 * The sign-in form carries a submitter/manager pill that picks the destination
 * surface: submitter lands on the mobile home, manager on the admin console. The
 * specs here exercise the console, so this helper always picks manager — which
 * lands on the **dashboard**, not the report list (use `openReports` for that).
 *
 * After this the token is in memory only — reach further screens by clicking nav
 * links, never by `page.goto` (a full reload clears it and bounces to sign-in).
 */
export async function login(page: Page): Promise<void> {
  await page.route('**/auth/login', (route) =>
    route.fulfill(json({ token: 'e2e-token', user: ADMIN_USER })),
  )
  await page.route('**/reports?*', (route) => route.fulfill(json(REPORT_LIST)))
  await page.route('**/organizations/*', (route) => route.fulfill(json(ORGANIZATION)))

  await page.goto('/')
  await page.getByLabel('メールアドレス').fill('admin@example.com')
  await page.getByLabel('パスワード').fill('password')
  await page.getByRole('button', { name: '承認者・管理者' }).click()
  await page.getByRole('button', { name: /管理者としてログイン/ }).click()

  await expect(page.getByRole('heading', { name: 'ダッシュボード', level: 1 })).toBeVisible()
}

/**
 * Moves from the dashboard to the report list by clicking the sidebar link.
 * The link label carries an icon and an unread count, so it is matched loosely.
 */
export async function openReports(page: Page): Promise<void> {
  await page.getByRole('link', { name: /日報一覧/ }).click()
  await expect(page.getByRole('heading', { name: '日報一覧', level: 1 })).toBeVisible()
}
