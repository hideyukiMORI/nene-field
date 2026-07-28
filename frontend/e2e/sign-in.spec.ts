import { expect, test } from '@playwright/test'
import { json, login, openReports, problem } from './helpers/auth'

test.describe('Sign in', () => {
  test('signs in as a manager and reaches the report list', async ({ page }) => {
    await login(page)
    await openReports(page)

    await expect(page.getByText('現場A 報告')).toBeVisible()
  })

  test('shows an error on invalid credentials', async ({ page }) => {
    await page.route('**/auth/login', (route) => route.fulfill(problem('unauthorized', 401)))

    await page.goto('/')
    await page.getByLabel('メールアドレス').fill('admin@example.com')
    await page.getByLabel('パスワード').fill('wrong')
    await page.getByRole('button', { name: /ログイン/ }).click()

    await expect(page.getByRole('alert')).toContainText(
      'メールアドレスまたはパスワードが正しくありません。',
    )
  })

  test('blocks submission with empty fields', async ({ page }) => {
    await page.route('**/auth/login', (route) => route.fulfill(json({ token: 'x', user: {} })))

    await page.goto('/')
    await page.getByRole('button', { name: /ログイン/ }).click()

    await expect(page.getByText('必須項目です。').first()).toBeVisible()
  })
})
