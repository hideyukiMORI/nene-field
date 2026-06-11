import { screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { http, HttpResponse } from 'msw'
import { describe, expect, it, vi } from 'vitest'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { server } from '@tests/msw/server'
import { TemplateList } from './TemplateList'

describe('TemplateList', () => {
  it('renders templates with the default badge and field count', async () => {
    renderWithProviders(<TemplateList />)

    expect(await screen.findByText('日報（標準）')).toBeInTheDocument()
    expect(screen.getByText('既定')).toBeInTheDocument()
    expect(screen.getByText('2 項目')).toBeInTheDocument()
  })

  it('deletes a template after confirmation', async () => {
    let deleted = false
    server.use(
      http.delete('/templates/:id', () => {
        deleted = true
        return new HttpResponse(null, { status: 204 })
      }),
    )
    vi.spyOn(window, 'confirm').mockReturnValue(true)
    const user = userEvent.setup()
    renderWithProviders(<TemplateList />)

    await screen.findByText('日報（標準）')
    await user.click(screen.getByRole('button', { name: '削除' }))

    await waitFor(() => {
      expect(deleted).toBe(true)
    })
  })

  it('does not delete when confirmation is dismissed', async () => {
    let deleted = false
    server.use(
      http.delete('/templates/:id', () => {
        deleted = true
        return new HttpResponse(null, { status: 204 })
      }),
    )
    vi.spyOn(window, 'confirm').mockReturnValue(false)
    const user = userEvent.setup()
    renderWithProviders(<TemplateList />)

    await screen.findByText('日報（標準）')
    await user.click(screen.getByRole('button', { name: '削除' }))

    expect(deleted).toBe(false)
  })
})
