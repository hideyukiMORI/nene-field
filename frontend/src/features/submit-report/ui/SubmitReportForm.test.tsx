import { screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { http, HttpResponse } from 'msw'
import { describe, expect, it, vi } from 'vitest'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { reportDetail } from '@tests/msw/handlers'
import { server } from '@tests/msw/server'
import { SubmitReportForm } from './SubmitReportForm'

describe('SubmitReportForm', () => {
  it('creates a draft then submits it', async () => {
    let created = false
    let submitted = false
    server.use(
      http.post('/reports', () => {
        created = true
        return HttpResponse.json(reportDetail('draft'), { status: 201 })
      }),
      http.post('/reports/:id/submit', () => {
        submitted = true
        return HttpResponse.json(reportDetail('submitted'))
      }),
    )
    const onDone = vi.fn()
    const user = userEvent.setup()
    renderWithProviders(<SubmitReportForm onDone={onDone} />)

    await user.type(screen.getByLabelText('タイトル'), '現場A 報告')
    await user.type(screen.getByLabelText('作業内容'), '本文です')
    await user.click(screen.getByRole('button', { name: '提出する' }))

    await waitFor(() => {
      expect(onDone).toHaveBeenCalledWith('r-1')
    })
    expect(created).toBe(true)
    expect(submitted).toBe(true)
  })

  it('saves a draft without submitting', async () => {
    let submitted = false
    server.use(
      http.post('/reports/:id/submit', () => {
        submitted = true
        return HttpResponse.json(reportDetail('submitted'))
      }),
    )
    const onDone = vi.fn()
    const user = userEvent.setup()
    renderWithProviders(<SubmitReportForm onDone={onDone} />)

    await user.type(screen.getByLabelText('タイトル'), '下書き')
    await user.type(screen.getByLabelText('作業内容'), 'まだ途中')
    await user.click(screen.getByRole('button', { name: '下書き保存' }))

    await waitFor(() => {
      expect(onDone).toHaveBeenCalledWith('r-1')
    })
    expect(submitted).toBe(false)
  })

  it('blocks submission with empty required fields', async () => {
    const onDone = vi.fn()
    const user = userEvent.setup()
    renderWithProviders(<SubmitReportForm onDone={onDone} />)

    await user.click(screen.getByRole('button', { name: '提出する' }))

    expect(await screen.findAllByText('必須項目です。')).not.toHaveLength(0)
    expect(onDone).not.toHaveBeenCalled()
  })
})
