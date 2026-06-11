import { screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it, vi } from 'vitest'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { UserCreateForm } from './UserCreateForm'

describe('UserCreateForm', () => {
  it('submits a valid new user', async () => {
    const onSubmit = vi.fn()
    const user = userEvent.setup()
    renderWithProviders(<UserCreateForm onSubmit={onSubmit} isPending={false} errorKey={null} />)

    await user.type(screen.getByLabelText('名前'), '新規ユーザー')
    await user.type(screen.getByLabelText('メールアドレス'), 'new@example.com')
    await user.type(screen.getByLabelText('パスワード'), 'password1')
    await user.click(screen.getByRole('button', { name: '作成' }))

    await waitFor(() => {
      expect(onSubmit).toHaveBeenCalledTimes(1)
    })
    const input = onSubmit.mock.calls[0]?.[0]
    expect(input).toMatchObject({
      name: '新規ユーザー',
      email: 'new@example.com',
      role: 'submitter',
      password: 'password1',
    })
  })

  it('rejects an invalid email and a short password', async () => {
    const onSubmit = vi.fn()
    const user = userEvent.setup()
    renderWithProviders(<UserCreateForm onSubmit={onSubmit} isPending={false} errorKey={null} />)

    await user.type(screen.getByLabelText('名前'), 'X')
    await user.type(screen.getByLabelText('メールアドレス'), 'not-an-email')
    await user.type(screen.getByLabelText('パスワード'), 'short')
    await user.click(screen.getByRole('button', { name: '作成' }))

    expect(await screen.findByText('形式が正しくありません。')).toBeInTheDocument()
    expect(screen.getByText('短すぎます。')).toBeInTheDocument()
    expect(onSubmit).not.toHaveBeenCalled()
  })
})
