import type { ReactNode, Ref, SelectHTMLAttributes } from 'react'
import { cn } from '@/shared/lib/cn'

interface SelectProps extends SelectHTMLAttributes<HTMLSelectElement> {
  ref?: Ref<HTMLSelectElement>
  children: ReactNode
}

export function Select({ className, children, ...rest }: SelectProps) {
  return (
    <select
      className={cn(
        'block w-full border border-border bg-surface-raised px-2 py-2 text-sm text-fg outline-none',
        'focus:border-accent focus:ring-2 focus:ring-accent-soft',
        'disabled:cursor-not-allowed disabled:opacity-50',
        className,
      )}
      {...rest}
    >
      {children}
    </select>
  )
}
