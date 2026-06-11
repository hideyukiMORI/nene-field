import type { ReactNode } from 'react'
import { cn } from '@/shared/lib/cn'

type BadgeTone = 'neutral' | 'info' | 'success' | 'danger' | 'warn'

interface BadgeProps {
  tone?: BadgeTone
  children: ReactNode
}

const toneClass: Record<BadgeTone, string> = {
  neutral: 'bg-surface-overlay text-fg-muted border-border',
  info: 'bg-info-soft text-info border-info',
  success: 'bg-success-soft text-success border-success',
  danger: 'bg-danger-soft text-danger border-danger',
  warn: 'bg-warn-soft text-warn border-warn',
}

export function Badge({ tone = 'neutral', children }: BadgeProps) {
  return (
    <span
      className={cn(
        'inline-flex items-center border px-2 py-0.5 text-xs font-semibold',
        toneClass[tone],
      )}
    >
      {children}
    </span>
  )
}
