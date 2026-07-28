import type { Meta, StoryObj } from '@storybook/react-vite'
import { InlineAlert } from './InlineAlert'

const meta: Meta<typeof InlineAlert> = {
  title: 'Components/InlineAlert',
  component: InlineAlert,
}

export default meta
type Story = StoryObj<typeof InlineAlert>

export const Error: Story = {
  args: { variant: 'error', children: 'メールアドレスまたはパスワードが正しくありません。' },
}
export const Success: Story = { args: { variant: 'success', children: '保存しました。' } }
export const Info: Story = { args: { variant: 'info', children: '下書きは自動保存されます。' } }
export const Warn: Story = {
  args: { variant: 'warn', children: 'セッションの有効期限が切れました。' },
}
