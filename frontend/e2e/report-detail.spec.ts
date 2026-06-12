import { expect, test } from '@playwright/test'
import { json, login } from './helpers/auth'

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

test.describe('Report detail', () => {
  test('opens a report from the list and shows review actions for an admin', async ({ page }) => {
    await page.route('**/reports/r-1', (route) => route.fulfill(json(REPORT_DETAIL)))
    await login(page)

    await page.getByRole('link', { name: '現場A 報告' }).click()

    await expect(page.getByRole('heading', { name: '現場A 報告' })).toBeVisible()
    await expect(page.getByText('本日の作業内容です。')).toBeVisible()
    // submitted + admin → review actions are shown
    await expect(page.getByRole('button', { name: '承認する' })).toBeVisible()
    await expect(page.getByRole('button', { name: '差し戻す' })).toBeVisible()
  })
})
