import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import {
  render,
  renderHook,
  type RenderHookResult,
  type RenderResult,
} from '@testing-library/react'
import { useState, type ReactElement, type ReactNode } from 'react'
import { MemoryRouter } from 'react-router-dom'
import { I18nProvider } from '@/shared/i18n'
import { ToastProvider } from '@/shared/ui'

export function createTestQueryClient(): QueryClient {
  return new QueryClient({
    defaultOptions: {
      queries: { retry: false },
      mutations: { retry: false },
    },
  })
}

function Providers({ children }: { children: ReactNode }) {
  const [queryClient] = useState(createTestQueryClient)
  return (
    <I18nProvider>
      <QueryClientProvider client={queryClient}>
        <MemoryRouter>
          <ToastProvider>{children}</ToastProvider>
        </MemoryRouter>
      </QueryClientProvider>
    </I18nProvider>
  )
}

export function renderWithProviders(ui: ReactElement): RenderResult {
  return render(ui, { wrapper: Providers })
}

/** renderHook with the same provider stack, for testing model-layer hooks directly. */
export function renderHookWithProviders<T>(hook: () => T): RenderHookResult<T, undefined> {
  return renderHook(hook, { wrapper: Providers })
}
