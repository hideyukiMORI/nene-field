import type { Preview } from '@storybook/react-vite'
// The design tokens (W1 vocabulary) plus the active theme — without this the
// catalogue renders unstyled and the a11y contrast checks are meaningless.
import '../src/shared/ui/theme/index.css'

const preview: Preview = {
  parameters: {
    controls: { matchers: { color: /(background|color)$/i, date: /Date$/i } },
  },
}

export default preview
