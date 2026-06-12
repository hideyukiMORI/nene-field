import type { Meta, StoryObj } from '@storybook/react-vite'
import { EmptyState } from './EmptyState'

const meta: Meta<typeof EmptyState> = {
  title: 'Components/EmptyState',
  component: EmptyState,
  args: { message: 'データがありません。' },
}

export default meta
type Story = StoryObj<typeof EmptyState>

export const Default: Story = {}
