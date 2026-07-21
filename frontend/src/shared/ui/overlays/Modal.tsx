import { useEffect } from 'react'
import type { ReactNode } from 'react'
import { cn } from '@/shared/lib/cn'

type ModalSize = 'sm' | 'md' | 'lg'

interface ModalProps {
  open: boolean
  onClose: () => void
  /** aria-label for the scrim / close button. I18N-18: pass t('common.actions.close'). */
  closeLabel: string
  title: ReactNode
  footer?: ReactNode
  size?: ModalSize
  children: ReactNode
}

const sizeClass: Record<ModalSize, string> = {
  sm: 'w-full max-w-md',
  md: 'w-full max-w-lg',
  lg: 'w-full max-w-2xl',
}

/** Centered modal (design handoff §3.7): scrim, pop-in panel, header / body / footer. */
export function Modal({
  open,
  onClose,
  closeLabel,
  title,
  footer,
  size = 'sm',
  children,
}: ModalProps) {
  useEffect(() => {
    if (!open) return
    const onKey = (e: KeyboardEvent) => {
      if (e.key === 'Escape') onClose()
    }
    window.addEventListener('keydown', onKey)
    return () => {
      window.removeEventListener('keydown', onKey)
    }
  }, [open, onClose])

  if (!open) return null

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-6">
      <button
        type="button"
        aria-label={closeLabel}
        className="absolute inset-0 bg-fg/45"
        onClick={onClose}
      />
      <div
        className={cn(
          'relative overflow-hidden rounded-modal bg-surface-raised shadow-modal animate-nfpop',
          sizeClass[size],
        )}
        role="dialog"
        aria-modal="true"
      >
        <div className="flex items-center gap-3 border-b border-border px-5 py-4">
          <h3 className="text-base font-bold text-fg">{title}</h3>
          <button
            type="button"
            onClick={onClose}
            aria-label={closeLabel}
            className="ml-auto text-lg leading-none text-fg-faint hover:text-fg"
          >
            ✕
          </button>
        </div>
        <div className="px-5 py-5">{children}</div>
        {footer !== undefined && (
          <div className="flex justify-end gap-2.5 border-t border-border bg-surface-raised px-5 py-3.5">
            {footer}
          </div>
        )}
      </div>
    </div>
  )
}
