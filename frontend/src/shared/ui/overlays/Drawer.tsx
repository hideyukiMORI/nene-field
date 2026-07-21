import { useEffect } from 'react'
import type { ReactNode } from 'react'

interface DrawerProps {
  open: boolean
  onClose: () => void
  /** aria-label for the scrim. I18N-18: pass t('common.actions.close'). */
  closeLabel: string
  /** Header content (e.g. prev/next nav + position counter for continuous review). */
  header: ReactNode
  footer?: ReactNode
  children: ReactNode
}

/** Right-sliding drawer (design handoff §3.8): scrim + 464px panel, slide-in. */
export function Drawer({ open, onClose, closeLabel, header, footer, children }: DrawerProps) {
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
    <div className="fixed inset-0 z-50 flex justify-end">
      <button
        type="button"
        aria-label={closeLabel}
        className="absolute inset-0 bg-fg/45"
        onClick={onClose}
      />
      <aside
        className="relative flex h-full w-full max-w-md flex-col bg-surface-raised shadow-drawer animate-nfslide"
        role="dialog"
        aria-modal="true"
      >
        <div className="flex items-center gap-3 border-b border-border px-5 py-4">{header}</div>
        <div className="flex-1 overflow-auto px-5 py-5">{children}</div>
        {footer !== undefined && (
          <div className="flex items-center gap-2.5 border-t border-border bg-surface-raised px-5 py-3.5">
            {footer}
          </div>
        )}
      </aside>
    </div>
  )
}
