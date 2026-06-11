import { Component, type ErrorInfo, type ReactNode } from 'react'
import { env } from '@/shared/config/env'
import { useTranslation } from '@/shared/i18n'
import { Button, Stack, Text } from '@/shared/ui'

/**
 * Fallback UI rendered when the boundary trips. A function component so the
 * translated copy can use {@link useTranslation} (the class boundary cannot call
 * hooks); it sits inside `I18nProvider` (see app/providers.tsx).
 */
function RootErrorFallback({ onReset }: { onReset: () => void }) {
  const { t } = useTranslation()
  return (
    <div className="flex min-h-screen items-center justify-center p-6">
      <Stack gap="md" className="max-w-sm text-center">
        <Text variant="title" as="h1">
          {t('error.generic')}
        </Text>
        <Button onClick={onReset}>{t('common.actions.retry')}</Button>
      </Stack>
    </div>
  )
}

interface RootErrorBoundaryProps {
  children: ReactNode
}

interface RootErrorBoundaryState {
  hasError: boolean
}

export class RootErrorBoundary extends Component<RootErrorBoundaryProps, RootErrorBoundaryState> {
  override state: RootErrorBoundaryState = { hasError: false }

  static getDerivedStateFromError(): RootErrorBoundaryState {
    return { hasError: true }
  }

  override componentDidCatch(error: Error, info: ErrorInfo): void {
    if (env.isDev) {
      console.error('Root error boundary caught', error, info)
    }
  }

  override render(): ReactNode {
    if (this.state.hasError) {
      return (
        <RootErrorFallback
          onReset={() => {
            this.setState({ hasError: false })
          }}
        />
      )
    }
    return this.props.children
  }
}
