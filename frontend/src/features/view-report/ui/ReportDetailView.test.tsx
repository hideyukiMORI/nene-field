import { screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { http, HttpResponse } from 'msw'
import { describe, expect, it, vi } from 'vitest'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { server } from '@tests/msw/server'
import { ReportDetailView } from './ReportDetailView'

describe('ReportDetailView', () => {
  it('renders the report detail (success state)', async () => {
    renderWithProviders(<ReportDetailView reportId="r-1" />)

    expect(await screen.findByRole('heading', { name: '現場A 報告' })).toBeInTheDocument()
    expect(screen.getByText('本文です')).toBeInTheDocument()
    expect(screen.getByText('田中太郎')).toBeInTheDocument()
    expect(screen.getByText('photo.png')).toBeInTheDocument()
  })

  it('shows the not-found state on 404', async () => {
    server.use(
      http.get('/reports/:id', () =>
        HttpResponse.json({ type: 'x/report-not-found', title: 'Not Found' }, { status: 404 }),
      ),
    )
    renderWithProviders(<ReportDetailView reportId="missing" />)

    expect(await screen.findByText('日報が見つかりませんでした。')).toBeInTheDocument()
  })

  it('downloads an attachment on click', async () => {
    const createObjectURL = vi.spyOn(globalThis.URL, 'createObjectURL')
    const user = userEvent.setup()
    renderWithProviders(<ReportDetailView reportId="r-1" />)

    await screen.findByText('photo.png')
    await user.click(screen.getByRole('button', { name: 'ダウンロード' }))

    await waitFor(() => {
      expect(createObjectURL).toHaveBeenCalled()
    })
  })

  it('renders the actions slot when provided', async () => {
    renderWithProviders(
      <ReportDetailView reportId="r-1" renderActions={() => <span>review-slot</span>} />,
    )

    expect(await screen.findByText('review-slot')).toBeInTheDocument()
  })
})
