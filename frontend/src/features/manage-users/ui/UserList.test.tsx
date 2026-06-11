import { screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { http, HttpResponse } from 'msw'
import { describe, expect, it, vi } from 'vitest'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { server } from '@tests/msw/server'
import { UserList } from './UserList'

describe('UserList', () => {
  it('renders users with role and status', async () => {
    renderWithProviders(<UserList />)

    expect(await screen.findByText('田中太郎')).toBeInTheDocument()
    expect(screen.getByText('tanaka@example.com')).toBeInTheDocument()
    expect(screen.getByText('承認者')).toBeInTheDocument()
    expect(screen.getByText('管理者')).toBeInTheDocument()
    expect(screen.getAllByText('有効').length).toBeGreaterThan(0)
  })

  it('deletes a user after confirmation', async () => {
    let deleted = false
    server.use(
      http.delete('/users/:id', () => {
        deleted = true
        return new HttpResponse(null, { status: 204 })
      }),
    )
    vi.spyOn(window, 'confirm').mockReturnValue(true)
    const user = userEvent.setup()
    renderWithProviders(<UserList />)

    await screen.findByText('田中太郎')
    const [firstDelete] = screen.getAllByRole('button', { name: '削除' })
    await user.click(firstDelete as HTMLElement)

    await waitFor(() => {
      expect(deleted).toBe(true)
    })
  })
})
