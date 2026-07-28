import type { Meta, StoryObj } from '@storybook/react-vite'
import { Spinner } from './Spinner'

const meta: Meta<typeof Spinner> = {
  title: 'Primitives/Spinner',
  component: Spinner,
}

export default meta
type Story = StoryObj<typeof Spinner>

/** `label` is required (I18N-18): the caller passes `t('common.state.loading')`. */
export const Default: Story = { args: { label: '読み込み中' } }
