import { screen } from '@testing-library/react'
import { http, HttpResponse } from 'msw'
import { describe, expect, it } from 'vitest'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { server } from '@tests/msw/server'
import { ListReports } from './ListReports'

describe('ListReports', () => {
  it('renders the reports table (success state)', async () => {
    renderWithProviders(<ListReports />)

    expect(await screen.findByText('現場A 報告')).toBeInTheDocument()
    expect(screen.getByText('田中太郎')).toBeInTheDocument()
    expect(screen.getByText('提出済み')).toBeInTheDocument()
  })

  it('renders the empty state when there are no reports', async () => {
    server.use(
      http.get('/reports', () => HttpResponse.json({ items: [], limit: 20, offset: 0, total: 0 })),
    )
    renderWithProviders(<ListReports />)

    expect(await screen.findByText('日報がまだありません。')).toBeInTheDocument()
  })

  it('renders the error state on a server error', async () => {
    server.use(
      http.get('/reports', () =>
        HttpResponse.json({ type: 'x/internal', title: 'Server Error' }, { status: 500 }),
      ),
    )
    renderWithProviders(<ListReports />)

    expect(await screen.findByText('日報の読み込みに失敗しました。')).toBeInTheDocument()
    expect(screen.getByRole('button', { name: '再試行' })).toBeInTheDocument()
  })
})
