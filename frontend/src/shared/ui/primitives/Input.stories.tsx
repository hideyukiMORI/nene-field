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
export const WithValue: Story = { args: { defaultValue: '現場A 報告' } }
export const Password: Story = { args: { type: 'password', defaultValue: 'password' } }
export const Disabled: Story = { args: { disabled: true, defaultValue: '編集できません' } }
