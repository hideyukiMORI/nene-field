import type { Meta, StoryObj } from '@storybook/react-vite'
import { Input } from './Input'

const meta: Meta<typeof Input> = {
  title: 'Primitives/Input',
  component: Input,
  args: { placeholder: '入力してください' },
}

export default meta
type Story = StoryObj<typeof Input>

export const Default: Story = {}
export const Disabled: Story = { args: { disabled: true, value: '読み取り専用' } }
export const Date: Story = { args: { type: 'date' } }
