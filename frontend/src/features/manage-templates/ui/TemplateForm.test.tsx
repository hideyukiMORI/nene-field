import { screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it, vi } from 'vitest'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { TemplateForm } from './TemplateForm'

describe('TemplateForm', () => {
  it('submits a created template with one field', async () => {
    const onSave = vi.fn()
    const user = userEvent.setup()
    renderWithProviders(
      <TemplateForm mode="create" onSave={onSave} isPending={false} errorKey={null} />,
    )

    await user.type(screen.getByLabelText('名前'), '新テンプレ')
    await user.type(screen.getByLabelText('項目名'), 'memo')
    await user.type(screen.getByLabelText('ラベル'), 'メモ')
    await user.click(screen.getByRole('button', { name: '保存' }))

    await waitFor(() => {
      expect(onSave).toHaveBeenCalledTimes(1)
    })
    const input = onSave.mock.calls[0]?.[0]
    expect(input.name).toBe('新テンプレ')
    expect(input.fields[0].name).toBe('memo')
    expect(input.fields[0].type).toBe('text')
  })

  it('adds and removes field rows', async () => {
    const user = userEvent.setup()
    renderWithProviders(
      <TemplateForm mode="create" onSave={vi.fn()} isPending={false} errorKey={null} />,
    )

    await user.click(screen.getByRole('button', { name: '項目を追加' }))
    expect(screen.getAllByLabelText('項目名')).toHaveLength(2)

    const [firstRemove] = screen.getAllByRole('button', { name: '削除' })
    await user.click(firstRemove as HTMLElement)
    expect(screen.getAllByLabelText('項目名')).toHaveLength(1)
  })

  it('requires options for a select field', async () => {
    const onSave = vi.fn()
    const user = userEvent.setup()
    renderWithProviders(
      <TemplateForm mode="create" onSave={onSave} isPending={false} errorKey={null} />,
    )

    await user.type(screen.getByLabelText('名前'), 'T')
    await user.type(screen.getByLabelText('項目名'), 'weather')
    await user.type(screen.getByLabelText('ラベル'), '天候')
    await user.selectOptions(screen.getByLabelText('種類'), 'select')
    await user.click(screen.getByRole('button', { name: '保存' }))

    expect(await screen.findByText('必須項目です。')).toBeInTheDocument()
    expect(onSave).not.toHaveBeenCalled()
  })

  it('populates from the initial template in edit mode', () => {
    renderWithProviders(
      <TemplateForm
        mode="edit"
        initialTemplate={{
          id: 't-1' as never,
          name: '既存テンプレ',
          description: null,
          isDefault: true,
          fields: [
            { name: 'summary', label: '作業内容', type: 'textarea', required: true, options: [] },
          ],
          createdAt: '2026-06-01 00:00:00',
          updatedAt: '2026-06-01 00:00:00',
        }}
        onSave={vi.fn()}
        isPending={false}
        errorKey={null}
      />,
    )

    expect(screen.getByDisplayValue('既存テンプレ')).toBeInTheDocument()
    expect(screen.getByDisplayValue('summary')).toBeInTheDocument()
  })
})
