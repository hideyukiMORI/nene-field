import { screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it } from 'vitest'
import { hasAuthToken } from '@/shared/api/client'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { SignIn } from './SignIn'

describe('SignIn', () => {
  it('signs in with valid credentials and sets the session token', async () => {
    const user = userEvent.setup()
    renderWithProviders(<SignIn />)

    await user.type(screen.getByLabelText('メールアドレス'), 'admin@example.com')
    await user.type(screen.getByLabelText('パスワード'), 'password')
    await user.click(screen.getByRole('button', { name: /としてログイン/ }))

    await waitFor(() => {
      expect(hasAuthToken()).toBe(true)
    })
  })

  it('shows an error message on invalid credentials', async () => {
    const user = userEvent.setup()
    renderWithProviders(<SignIn />)

    await user.type(screen.getByLabelText('メールアドレス'), 'admin@example.com')
    await user.type(screen.getByLabelText('パスワード'), 'wrong')
    await user.click(screen.getByRole('button', { name: /としてログイン/ }))

    expect(await screen.findByRole('alert')).toHaveTextContent(
      'メールアドレスまたはパスワードが正しくありません。',
    )
    expect(hasAuthToken()).toBe(false)
  })

  it('blocks submission when fields are empty (UX validation)', async () => {
    const user = userEvent.setup()
    renderWithProviders(<SignIn />)

    await user.click(screen.getByRole('button', { name: /としてログイン/ }))

    expect(hasAuthToken()).toBe(false)
    expect(screen.getAllByText('必須項目です。').length).toBeGreaterThan(0)
  })
})
