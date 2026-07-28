import type { StorybookConfig } from '@storybook/react-vite'

/**
 * Catalogue of the shared UI layer only. Stories live next to the component they
 * document, under src/shared/ui — feature and page composites are covered by the
 * vitest suite and the Playwright specs, not here.
 */
const config: StorybookConfig = {
  stories: ['../src/shared/ui/**/*.stories.@(ts|tsx)'],
  addons: ['@storybook/addon-a11y'],
  framework: {
    name: '@storybook/react-vite',
    options: {},
  },
}

export default config
