import type { ReactNode } from 'react'
import { cn } from '@/shared/lib/cn'

/**
 * Tones (design handoff §1.3 / §3.2). Pill-shaped. Report-status tones map to the
 * dedicated status tokens; `ai` is a filled accent for the AI-summary badge.
 */
type BadgeTone =
  | 'neutral'
  | 'info'
  | 'success'
  | 'danger'
  | 'warn'
  | 'submitted'
  | 'approved'
  | 'rejected'
  | 'draft'
  | 'ai'

interface BadgeProps {
  tone?: BadgeTone
  className?: string
  children: ReactNode
}

const toneClass: Record<BadgeTone, string> = {
  neutral: 'bg-surface-overlay text-fg-muted',
  info: 'bg-info-soft text-info',
  success: 'bg-success-soft text-success',
  danger: 'bg-danger-soft text-danger',
  warn: 'bg-warn-soft text-warn',
  submitted: 'bg-submitted-soft text-submitted',
  approved: 'bg-approved-soft text-approved',
  rejected: 'bg-rejected-soft text-rejected',
  draft: 'bg-draft-soft text-draft',
  ai: 'bg-ai text-fg-inverse',
}

export function Badge({ tone = 'neutral', className, children }: BadgeProps) {
  return (
    <span
      className={cn(
        'inline-flex items-center gap-1.5 rounded-pill px-2.5 py-0.5 text-xs font-semibold leading-tight',
        toneClass[tone],
        className,
      )}
    >
      {children}
    </span>
  )
}
