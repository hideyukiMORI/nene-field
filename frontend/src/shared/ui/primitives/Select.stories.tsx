import type { Meta, StoryObj } from '@storybook/react-vite'
import { Select } from './Select'

const meta: Meta<typeof Select> = {
  title: 'Primitives/Select',
  component: Select,
  render: (args) => (
    <Select {...args}>
      <option value="submitter">提出者</option>
      <option value="approver">承認者</option>
      <option value="admin">管理者</option>
    </Select>
  ),
}

export default meta
type Story = StoryObj<typeof Select>

export const Default: Story = {}
export const Disabled: Story = { args: { disabled: true } }
