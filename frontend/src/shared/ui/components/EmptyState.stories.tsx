import type { Meta, StoryObj } from '@storybook/react-vite'
import { EmptyState } from './EmptyState'

const meta: Meta<typeof EmptyState> = {
  title: 'Components/States/EmptyState',
  component: EmptyState,
}

export default meta
type Story = StoryObj<typeof EmptyState>

export const Default: Story = { args: { message: '日報がまだありません。' } }
