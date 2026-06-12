import type { Meta, StoryObj } from '@storybook/react-vite'
import { Badge } from './Badge'

const meta: Meta<typeof Badge> = {
  title: 'Components/Badge',
  component: Badge,
  args: { children: '承認済み' },
}

export default meta
type Story = StoryObj<typeof Badge>

export const Neutral: Story = { args: { tone: 'neutral', children: '下書き' } }
export const Info: Story = { args: { tone: 'info', children: '提出済み' } }
export const Success: Story = { args: { tone: 'success', children: '承認済み' } }
export const Danger: Story = { args: { tone: 'danger', children: '差し戻し' } }
export const Warn: Story = { args: { tone: 'warn', children: '保留' } }
