import { createContext } from 'react'

export interface ToastApi {
  /** Show a transient confirmation toast (auto-dismisses after ~2.2s). */
  show: (message: string) => void
}

export const ToastContext = createContext<ToastApi | null>(null)
