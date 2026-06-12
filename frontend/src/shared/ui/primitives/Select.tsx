import type { ReactNode, Ref, SelectHTMLAttributes } from 'react'
import { cn } from '@/shared/lib/cn'

interface SelectProps extends SelectHTMLAttributes<HTMLSelectElement> {
  ref?: Ref<HTMLSelectElement>
  children: ReactNode
}

export function Select({ className, children, ...rest }: SelectProps) {
  return (
    <div className="relative w-full">
      <select
        className={cn(
          'block w-full appearance-none rounded-input border border-border-input bg-surface-raised px-3 py-2.5 pr-9 text-sm text-fg outline-none',
          'focus:border-accent focus:ring-2 focus:ring-accent-soft',
          'disabled:cursor-not-allowed disabled:opacity-50',
          className,
        )}
        {...rest}
      >
        {children}
      </select>
      <span
        aria-hidden
        className="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-xs text-fg-faint"
      >
        ▾
      </span>
    </div>
  )
}
