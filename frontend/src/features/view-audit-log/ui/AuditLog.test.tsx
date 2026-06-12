import { fireEvent, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it, vi } from 'vitest'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { AuditLog } from './AuditLog'

describe('AuditLog', () => {
  it('renders audit events', async () => {
    renderWithProviders(<AuditLog />)

    expect(await screen.findByText('report.approved')).toBeInTheDocument()
    expect(screen.getByText('user.created')).toBeInTheDocument()
    expect(screen.getAllByText('管理者').length).toBeGreaterThan(0)
  })

  it('enables and runs the CSV export once a date range is set', async () => {
    const createObjectURL = vi.spyOn(globalThis.URL, 'createObjectURL')
    const user = userEvent.setup()
    renderWithProviders(<AuditLog />)

    await screen.findByText('report.approved')
    expect(screen.getByRole('button', { name: /CSVダウンロード/ })).toBeDisabled()

    fireEvent.change(screen.getByLabelText('開始日'), { target: { value: '2026-06-01' } })
    fireEvent.change(screen.getByLabelText('終了日'), { target: { value: '2026-06-30' } })

    const exportButton = screen.getByRole('button', { name: /CSVダウンロード/ })
    expect(exportButton).toBeEnabled()
    await user.click(exportButton)

    await waitFor(() => {
      expect(createObjectURL).toHaveBeenCalled()
    })
  })
})
