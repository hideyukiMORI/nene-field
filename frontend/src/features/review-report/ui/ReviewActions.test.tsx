import { screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { http, HttpResponse } from 'msw'
import { describe, expect, it } from 'vitest'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { server } from '@tests/msw/server'
import { reportDetail } from '@tests/msw/handlers'
import { ReviewActions } from './ReviewActions'

describe('ReviewActions', () => {
  it('approves the report on click', async () => {
    let approved = false
    server.use(
      http.post('/reports/:id/approve', () => {
        approved = true
        return HttpResponse.json(reportDetail('approved'))
      }),
    )
    const user = userEvent.setup()
    renderWithProviders(<ReviewActions reportId="r-1" />)

    await user.click(screen.getByRole('button', { name: '承認する' }))

    await waitFor(() => {
      expect(approved).toBe(true)
    })
  })

  it('requires a comment to send back', async () => {
    let rejected = false
    server.use(
      http.post('/reports/:id/reject', () => {
        rejected = true
        return HttpResponse.json(reportDetail('rejected'))
      }),
    )
    const user = userEvent.setup()
    renderWithProviders(<ReviewActions reportId="r-1" />)

    // open the reject form, submit without a comment
    await user.click(screen.getByRole('button', { name: '差し戻す' }))
    await user.click(screen.getByRole('button', { name: '差し戻す' }))

    expect(screen.getByText('差し戻しにはコメントが必要です。')).toBeInTheDocument()
    expect(rejected).toBe(false)

    // now enter a comment and submit
    await user.type(screen.getByLabelText('コメント'), '修正してください')
    await user.click(screen.getByRole('button', { name: '差し戻す' }))

    await waitFor(() => {
      expect(rejected).toBe(true)
    })
  })
})
