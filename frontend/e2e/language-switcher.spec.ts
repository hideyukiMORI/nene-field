import { expect, test } from '@playwright/test'
import { login } from './helpers/auth'

test.describe('Language switcher', () => {
  test('switches the UI between Japanese and English at runtime', async ({ page }) => {
    await login(page)

    await expect(page.getByRole('heading', { name: '日報一覧' })).toBeVisible()

    await page.getByLabel('言語').selectOption('en')
    await expect(page.getByRole('heading', { name: 'Reports' })).toBeVisible()

    await page.getByLabel('Language').selectOption('ja')
    await expect(page.getByRole('heading', { name: '日報一覧' })).toBeVisible()
  })
})
