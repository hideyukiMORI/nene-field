import { expect, test } from '@playwright/test'
import { json, login, problem } from './helpers/auth'

test.describe('Sign in', () => {
  test('logs in and lands on the reports list', async ({ page }) => {
    await login(page)

    await expect(page.getByRole('heading', { name: '日報一覧' })).toBeVisible()
    await expect(page.getByText('現場A 報告')).toBeVisible()
  })

  test('shows an error on invalid credentials', async ({ page }) => {
    await page.route('**/auth/login', (route) => route.fulfill(problem('unauthorized', 401)))

    await page.goto('/')
    await page.getByLabel('メールアドレス').fill('admin@example.com')
    await page.getByLabel('パスワード').fill('wrong')
    await page.getByRole('button', { name: 'ログイン' }).click()

    await expect(page.getByRole('alert')).toContainText(
      'メールアドレスまたはパスワードが正しくありません。',
    )
  })

  test('signs out back to the login screen', async ({ page }) => {
    await login(page)

    await page.getByRole('button', { name: 'ログアウト' }).click()

    await expect(page.getByLabel('メールアドレス')).toBeVisible()
    await expect(page.getByRole('button', { name: 'ログイン' })).toBeVisible()
  })

  test('blocks submission with empty fields', async ({ page }) => {
    await page.route('**/auth/login', (route) => route.fulfill(json({ token: 'x', user: {} })))

    await page.goto('/')
    await page.getByRole('button', { name: 'ログイン' }).click()

    await expect(page.getByText('必須項目です。').first()).toBeVisible()
  })
})
