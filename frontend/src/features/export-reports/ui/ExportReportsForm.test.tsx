import { fireEvent, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { http, HttpResponse } from 'msw'
import { describe, expect, it, vi } from 'vitest'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { server } from '@tests/msw/server'
import { ExportReportsForm } from './ExportReportsForm'

describe('ExportReportsForm', () => {
  it('disables download until a work-date range is set', () => {
    renderWithProviders(<ExportReportsForm />)
    expect(screen.getByRole('button', { name: /CSVダウンロード/ })).toBeDisabled()
  })

  it('downloads with the chosen filters', async () => {
    let requestUrl = ''
    server.use(
      http.get('/export/csv', ({ request }) => {
        requestUrl = request.url
        return HttpResponse.arrayBuffer(new TextEncoder().encode('report_id\r\n').buffer, {
          headers: { 'Content-Type': 'text/csv; charset=utf-8' },
        })
      }),
    )
    const createObjectURL = vi.spyOn(globalThis.URL, 'createObjectURL')
    const user = userEvent.setup()
    renderWithProviders(<ExportReportsForm />)

    fireEvent.change(screen.getByLabelText('作業日（開始）'), { target: { value: '2026-06-01' } })
    fireEvent.change(screen.getByLabelText('作業日（終了）'), { target: { value: '2026-06-30' } })
    await user.click(screen.getByRole('button', { name: /CSVダウンロード/ }))

    await waitFor(() => {
      expect(createObjectURL).toHaveBeenCalled()
    })
    expect(requestUrl).toContain('work_date_from=2026-06-01')
    expect(requestUrl).toContain('work_date_to=2026-06-30')
    // default status (approved) is sent as an array param
    expect(requestUrl).toContain('status%5B%5D=approved')
  })
})
