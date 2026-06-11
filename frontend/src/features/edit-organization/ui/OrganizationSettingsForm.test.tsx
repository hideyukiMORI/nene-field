import { screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it, vi } from 'vitest'
import type { Organization } from '@/entities/organization'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { OrganizationSettingsForm } from './OrganizationSettingsForm'

const organization: Organization = {
  id: 'org-1' as never,
  name: '山田造園',
  slug: 'yamada',
  customDomain: null,
  isActive: true,
  aiSummaryEnabled: false,
  notificationEmail: 'kanri@example.com',
  webhookUrl: null,
  createdAt: '2026-06-01 00:00:00',
  updatedAt: '2026-06-10 00:00:00',
}

function render(onSave = vi.fn()) {
  renderWithProviders(
    <OrganizationSettingsForm
      organization={organization}
      onSave={onSave}
      isPending={false}
      isSaved={false}
      errorKey={null}
    />,
  )
  return onSave
}

describe('OrganizationSettingsForm', () => {
  it('prefills from the organization and shows read-only info', () => {
    render()
    expect(screen.getByDisplayValue('山田造園')).toBeInTheDocument()
    expect(screen.getByDisplayValue('kanri@example.com')).toBeInTheDocument()
    expect(screen.getByText('yamada')).toBeInTheDocument()
    expect(screen.getByText('有効')).toBeInTheDocument()
  })

  it('submits the editable fields', async () => {
    const onSave = render()
    const user = userEvent.setup()

    await user.click(screen.getByRole('button', { name: '保存' }))

    await waitFor(() => {
      expect(onSave).toHaveBeenCalledTimes(1)
    })
    expect(onSave.mock.calls[0]?.[0]).toStrictEqual({
      name: '山田造園',
      aiSummaryEnabled: false,
      notificationEmail: 'kanri@example.com',
      webhookUrl: null,
    })
  })

  it('rejects an invalid notification email', async () => {
    const onSave = render()
    const user = userEvent.setup()

    const email = screen.getByLabelText('通知メールアドレス（任意）')
    await user.clear(email)
    await user.type(email, 'not-an-email')
    await user.click(screen.getByRole('button', { name: '保存' }))

    expect(await screen.findByText('形式が正しくありません。')).toBeInTheDocument()
    expect(onSave).not.toHaveBeenCalled()
  })
})
