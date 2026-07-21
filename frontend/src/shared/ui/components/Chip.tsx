import type { ReactNode } from 'react'
import { cn } from '@/shared/lib/cn'

interface ChipProps {
  active?: boolean
  onClick?: () => void
  className?: string
  children: ReactNode
}

/**
 * Pill chip for roles / tags / filters (design handoff §3.3). Renders as a
 * button when `onClick` is supplied (interactive filter), otherwise a static
 * span. Active = accent fill; inactive = white with a neutral border.
 */
export function Chip({ active = false, onClick, className, children }: ChipProps) {
  const classes = cn(
    'inline-flex items-center gap-1.5 rounded-x-pill px-3.5 py-1.5 text-xs font-semibold transition-colors',
    active
      ? 'bg-accent text-text-inverse'
      : 'border border-x-border-input bg-surface-raised text-text-muted',
    onClick !== undefined && 'cursor-pointer',
    className,
  )
  if (onClick !== undefined) {
    return (
      <button type="button" onClick={onClick} aria-pressed={active} className={classes}>
        {children}
      </button>
    )
  }
  return <span className={classes}>{children}</span>
}
