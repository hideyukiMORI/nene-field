import { screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it, vi } from 'vitest'
import type { User } from '@/entities/user'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { UserEditForm } from './UserEditForm'

const user: User = {
  id: 'u-1' as never,
  organizationId: 'org-1',
  name: '田中太郎',
  email: 'tanaka@example.com',
  role: 'approver',
  isActive: true,
  createdAt: '2026-06-01 00:00:00',
  updatedAt: '2026-06-01 00:00:00',
}

describe('UserEditForm', () => {
  it('prefills from the user and email is read-only', () => {
    renderWithProviders(
      <UserEditForm user={user} onSubmit={vi.fn()} isPending={false} errorKey={null} />,
    )

    expect(screen.getByDisplayValue('田中太郎')).toBeInTheDocument()
    const email = screen.getByLabelText('メールアドレス')
    expect(email).toHaveValue('tanaka@example.com')
    expect(email).toBeDisabled()
  })

  it('submits the editable fields', async () => {
    const onSubmit = vi.fn()
    const actor = userEvent.setup()
    renderWithProviders(
      <UserEditForm user={user} onSubmit={onSubmit} isPending={false} errorKey={null} />,
    )

    await actor.click(screen.getByRole('button', { name: '保存' }))

    await waitFor(() => {
      expect(onSubmit).toHaveBeenCalledTimes(1)
    })
    expect(onSubmit.mock.calls[0]?.[0]).toStrictEqual({
      name: '田中太郎',
      role: 'approver',
      isActive: true,
    })
  })
})
