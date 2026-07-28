import { expect, test, type Locator, type Page } from '@playwright/test'
import { login, openReports } from './helpers/auth'

/**
 * The manager console reviews a report in a **drawer** opened from the list row —
 * it does not navigate to `/reports/:id` (that route belongs to the submitter
 * surface, see mobile-report-detail.spec.ts). The row also carries inline
 * approve/reject buttons, so the drawer assertions are scoped to the dialog.
 *
 * The row is opened by clicking its work-date cell: the title appears twice in the
 * row (once as the select checkbox's label, once in the title/AI column), and the
 * checkbox cell deliberately stops click propagation.
 */
async function openReviewDrawer(page: Page): Promise<Locator> {
  await page
    .getByRole('row', { name: /現場A 報告/ })
    .getByText('2026-06-11')
    .click()
  const drawer = page.getByRole('dialog')
  await expect(drawer).toBeVisible()
  return drawer
}

test.describe('Report review (manager drawer)', () => {
  test('opens the review drawer from a list row and offers the review actions', async ({
    page,
  }) => {
    await login(page)
    await openReports(page)

    const drawer = await openReviewDrawer(page)

    await expect(drawer.getByText('現場A 報告')).toBeVisible()
    // submitted → the review actions are offered in the drawer footer
    await expect(drawer.getByRole('button', { name: '承認する' })).toBeVisible()
    await expect(drawer.getByRole('button', { name: '差し戻す' })).toBeVisible()
  })

  test('requires a comment before a rejection can be confirmed', async ({ page }) => {
    await login(page)
    await openReports(page)

    const drawer = await openReviewDrawer(page)
    await drawer.getByRole('button', { name: '差し戻す' }).click()

    // The reject modal opens on top of the drawer; its confirm button stays
    // disabled until a reason is typed (report.review.commentRequired).
    const modal = page.getByRole('dialog').last()
    const confirm = modal.getByRole('button', { name: '差し戻す' })
    await expect(confirm).toBeDisabled()

    await modal.getByRole('textbox').fill('作業時間の記載が不足しています。')
    await expect(confirm).toBeEnabled()
  })
})
