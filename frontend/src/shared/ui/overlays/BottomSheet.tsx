import { useEffect } from 'react'
import type { ReactNode } from 'react'

interface BottomSheetProps {
  open: boolean
  onClose: () => void
  title?: ReactNode
  footer?: ReactNode
  children: ReactNode
}

/** Mobile bottom sheet (design handoff §3.9): slides up from the bottom with a grab handle. */
export function BottomSheet({ open, onClose, title, footer, children }: BottomSheetProps) {
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
    <div className="absolute inset-0 z-50 flex items-end">
      <button
        type="button"
        aria-label="閉じる"
        className="absolute inset-0 bg-fg/50"
        onClick={onClose}
      />
      <div
        className="relative w-full rounded-t-sheet bg-surface-raised pb-6 shadow-modal animate-nfup"
        role="dialog"
        aria-modal="true"
      >
        <div className="flex justify-center pt-2.5 pb-1">
          <span className="h-1 w-10 rounded-pill bg-border-strong" />
        </div>
        {title !== undefined && (
          <div className="px-5 pt-1 pb-3 text-base font-bold text-fg">{title}</div>
        )}
        <div className="px-5">{children}</div>
        {footer !== undefined && <div className="flex gap-2.5 px-5 pt-4">{footer}</div>}
      </div>
    </div>
  )
}
