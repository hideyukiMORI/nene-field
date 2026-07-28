import type { Meta, StoryObj } from '@storybook/react-vite'
import { Select } from './Select'

const meta: Meta<typeof Select> = {
  title: 'Primitives/Select',
  component: Select,
}

export default meta
type Story = StoryObj<typeof Select>

const options = (
  <>
    <option value="draft">下書き</option>
    <option value="submitted">提出済み</option>
    <option value="approved">承認済み</option>
  </>
)

export const Default: Story = { args: { children: options } }
export const Disabled: Story = { args: { children: options, disabled: true } }
