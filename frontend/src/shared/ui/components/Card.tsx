import type { HTMLAttributes, ReactNode } from 'react'
import { cn } from '@/shared/lib/cn'

interface CardProps extends HTMLAttributes<HTMLDivElement> {
  /** Apply the standard inner padding (16–22px). Set false for custom layouts. */
  padded?: boolean
  children: ReactNode
}

/** Surface card (design handoff §3.4): white, hairline border, rounded, soft shadow. */
export function Card({ padded = true, className, children, ...rest }: CardProps) {
  return (
    <div
      className={cn(
        'rounded-x-card border border-border bg-surface-raised shadow-x-card',
        padded && 'p-5',
        className,
      )}
      {...rest}
    >
      {children}
    </div>
  )
}
