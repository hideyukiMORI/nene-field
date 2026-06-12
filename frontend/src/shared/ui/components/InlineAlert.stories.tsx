import type { Meta, StoryObj } from '@storybook/react-vite'
import { InlineAlert } from './InlineAlert'

const meta: Meta<typeof InlineAlert> = {
  title: 'Components/InlineAlert',
  component: InlineAlert,
  args: { children: 'メッセージ' },
}

export default meta
type Story = StoryObj<typeof InlineAlert>

export const Info: Story = { args: { variant: 'info', children: 'お知らせ' } }
export const Success: Story = { args: { variant: 'success', children: '保存しました。' } }
export const Warn: Story = {
  args: { variant: 'warn', children: 'セッションの有効期限が切れました。' },
}
export const ErrorAlert: Story = { args: { variant: 'error', children: '問題が発生しました。' } }
