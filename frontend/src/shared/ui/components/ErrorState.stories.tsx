import type { Meta, StoryObj } from '@storybook/react-vite'
import { ErrorState } from './ErrorState'

const meta: Meta<typeof ErrorState> = {
  title: 'Components/States/ErrorState',
  component: ErrorState,
}

export default meta
type Story = StoryObj<typeof ErrorState>

export const Default: Story = {
  args: {
    message: '日報の読み込みに失敗しました。',
    retryLabel: '再試行',
    onRetry: () => undefined,
  },
}
