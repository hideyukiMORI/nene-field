import type { Meta, StoryObj } from '@storybook/react-vite'
import { Button } from './Button'

const meta: Meta<typeof Button> = {
  title: 'Primitives/Button',
  component: Button,
  args: { children: '保存する' },
}

export default meta
type Story = StoryObj<typeof Button>

export const Primary: Story = { args: { variant: 'primary' } }
export const Success: Story = { args: { variant: 'success', children: '承認する' } }
export const Danger: Story = { args: { variant: 'danger', children: '削除する' } }
export const DangerGhost: Story = { args: { variant: 'danger-ghost', children: '差し戻す' } }
export const Secondary: Story = { args: { variant: 'secondary', children: 'キャンセル' } }
export const Ghost: Story = { args: { variant: 'ghost', children: '閉じる' } }
export const Disabled: Story = { args: { disabled: true } }

/** The three sizes side by side — `sm` is used inside table rows, `lg` for mobile CTAs. */
export const Sizes: Story = {
  render: () => (
    <div className="flex items-center gap-2">
      <Button size="sm">小</Button>
      <Button size="md">中</Button>
      <Button size="lg">大</Button>
    </div>
  ),
}
