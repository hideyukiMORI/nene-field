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

const REPORT_LIST = {
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

/**
 * Logs in through the real sign-in form (stubbing `/auth/login` and the reports
 * list that the shell loads), then waits for the authenticated app shell. After
 * this the in-memory token is set; reach features by clicking nav links — never
 * by `page.goto` (a full reload clears the token).
 */
export async function login(page: Page): Promise<void> {
  await page.route('**/auth/login', (route) =>
    route.fulfill(json({ token: 'e2e-token', user: ADMIN_USER })),
  )
  await page.route('**/reports?*', (route) => route.fulfill(json(REPORT_LIST)))

  await page.goto('/')
  await page.getByLabel('メールアドレス').fill('admin@example.com')
  await page.getByLabel('パスワード').fill('password')
  await page.getByRole('button', { name: 'ログイン' }).click()

  await expect(page.getByRole('heading', { name: '日報一覧' })).toBeVisible()
}
