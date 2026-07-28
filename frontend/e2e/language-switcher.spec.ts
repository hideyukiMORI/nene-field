import { expect, test } from '@playwright/test'
import { login } from './helpers/auth'

test.describe('Language switcher', () => {
  test('switches the UI between Japanese and English at runtime', async ({ page }) => {
    await login(page)

    // The locale lives in React state, so switching it must re-render the whole
    // shell — assert on the sidebar nav, not only on the screen that owns the toggle.
    await expect(page.getByRole('link', { name: /日報一覧/ })).toBeVisible()

    await page.getByRole('button', { name: 'EN' }).click()
    await expect(page.getByRole('link', { name: /Reports/ })).toBeVisible()
    await expect(page.getByRole('heading', { name: 'Dashboard', level: 1 })).toBeVisible()

    await page.getByRole('button', { name: '日本語' }).click()
    await expect(page.getByRole('link', { name: /日報一覧/ })).toBeVisible()
    await expect(page.getByRole('heading', { name: 'ダッシュボード', level: 1 })).toBeVisible()
  })
})
