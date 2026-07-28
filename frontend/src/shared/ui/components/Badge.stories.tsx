import type { Meta, StoryObj } from '@storybook/react-vite'
import { Badge } from './Badge'

const meta: Meta<typeof Badge> = {
  title: 'Components/Badge',
  component: Badge,
  args: { children: 'ラベル' },
}

export default meta
type Story = StoryObj<typeof Badge>

export const Neutral: Story = { args: { tone: 'neutral' } }
export const Info: Story = { args: { tone: 'info' } }

/** The four report-status tones, in lifecycle order. */
export const ReportStatuses: Story = {
  render: () => (
    <div className="flex flex-wrap items-center gap-2">
      <Badge tone="draft">下書き</Badge>
      <Badge tone="submitted">提出済み</Badge>
      <Badge tone="approved">承認済み</Badge>
      <Badge tone="rejected">差し戻し</Badge>
    </div>
  ),
}

export const Semantic: Story = {
  render: () => (
    <div className="flex flex-wrap items-center gap-2">
      <Badge tone="success">成功</Badge>
      <Badge tone="warn">注意</Badge>
      <Badge tone="danger">エラー</Badge>
      <Badge tone="ai">AI 要約</Badge>
    </div>
  ),
}
