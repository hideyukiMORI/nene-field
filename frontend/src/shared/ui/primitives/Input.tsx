import type { InputHTMLAttributes, Ref } from 'react'
import { cn } from '@/shared/lib/cn'

interface InputProps extends InputHTMLAttributes<HTMLInputElement> {
  ref?: Ref<HTMLInputElement>
}

export function Input({ className, ...rest }: InputProps) {
  return (
    <input
      className={cn(
        'block w-full border border-border bg-surface-raised px-2 py-2 text-sm text-fg outline-none',
        'focus:border-accent focus:ring-2 focus:ring-accent-soft',
        'disabled:cursor-not-allowed disabled:opacity-50',
        className,
      )}
      {...rest}
    />
  )
}
