import { useCallback, useMemo, useRef, useState } from 'react'
import type { ReactNode } from 'react'
import { ToastContext } from './toast-context'
import type { ToastApi } from './toast-context'

interface ToastState {
  id: number
  message: string
}

/**
 * Toast host + context (design handoff §3.10): a single transient confirmation
 * pill, centered at the bottom with a green check, auto-dismissing after 2.2s.
 */
export function ToastProvider({ children }: { children: ReactNode }) {
  const [toast, setToast] = useState<ToastState | null>(null)
  const timer = useRef<ReturnType<typeof setTimeout> | null>(null)

  const show = useCallback((message: string) => {
    if (timer.current !== null) clearTimeout(timer.current)
    setToast({ id: Date.now(), message })
    timer.current = setTimeout(() => {
      setToast(null)
    }, 2200)
  }, [])

  const api = useMemo<ToastApi>(() => ({ show }), [show])

  return (
    <ToastContext.Provider value={api}>
      {children}
      {toast !== null && (
        <div
          className="pointer-events-none fixed inset-x-0 bottom-8 z-50 flex justify-center px-4"
          aria-live="polite"
        >
          <div
            key={toast.id}
            className="inline-flex items-center gap-2 rounded-pill bg-fg px-6 py-3 text-sm font-semibold text-fg-inverse shadow-modal animate-nfup"
          >
            <span className="text-toast-check">✓</span>
            {toast.message}
          </div>
        </div>
      )}
    </ToastContext.Provider>
  )
}
