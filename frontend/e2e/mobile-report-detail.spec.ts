import { expect, test } from '@playwright/test'
import { json, REPORT_LIST, SUBMITTER_USER } from './helpers/auth'

const REPORT_DETAIL = {
  report_id: 'r-1',
  organization_id: 'org-1',
  user_id: 'u-1',
  user_name: '田中太郎',
  title: '現場A 報告',
  body: '本日の作業内容です。',
  work_date: '2026-06-11',
  status: 'submitted',
  tags: [],
  submitted_at: '2026-06-11 09:00:00',
  attachments: [],
  created_at: '2026-06-11 08:00:00',
  updated_at: '2026-06-11 08:00:00',
}

/**
 * `/reports/:id` is the submitter surface's detail screen — the manager console
 * reviews in a drawer instead (see report-review.spec.ts). Signing in with the
 * submitter pill is what routes to the mobile shell.
 */
test.describe('Report detail (submitter surface)', () => {
  test('opens a report from the mobile list and shows its body', async ({ page }) => {
    await page.route('**/auth/login', (route) =>
      route.fulfill(json({ token: 'e2e-token', user: SUBMITTER_USER })),
    )
    await page.route('**/reports?*', (route) => route.fulfill(json(REPORT_LIST)))
    await page.route('**/reports/r-1', (route) => route.fulfill(json(REPORT_DETAIL)))

    await page.goto('/')
    await page.getByLabel('メールアドレス').fill('tanaka@example.com')
    await page.getByLabel('パスワード').fill('password')
    await page.getByRole('button', { name: '提出者', exact: true }).click()
    await page.getByRole('button', { name: /提出者としてログイン/ }).click()

    await page
      .getByRole('link', { name: /現場A 報告/ })
      .first()
      .click()

    await expect(page.getByRole('heading', { name: '現場A 報告' })).toBeVisible()
    await expect(page.getByText('本日の作業内容です。')).toBeVisible()
  })
})
