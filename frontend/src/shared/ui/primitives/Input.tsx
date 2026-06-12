import type { InputHTMLAttributes, Ref } from 'react'
import { cn } from '@/shared/lib/cn'

interface InputProps extends InputHTMLAttributes<HTMLInputElement> {
  ref?: Ref<HTMLInputElement>
}

export function Input({ className, ...rest }: InputProps) {
  return (
    <input
      className={cn(
        'block w-full rounded-input border border-border-input bg-surface-raised px-3 py-2.5 text-sm text-fg outline-none',
        'placeholder:text-fg-faint focus:border-accent focus:ring-2 focus:ring-accent-soft',
        'disabled:cursor-not-allowed disabled:opacity-50',
        className,
      )}
      {...rest}
    />
  )
}
