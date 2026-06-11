import type { Ref, TextareaHTMLAttributes } from 'react'
import { cn } from '@/shared/lib/cn'

interface TextareaProps extends TextareaHTMLAttributes<HTMLTextAreaElement> {
  ref?: Ref<HTMLTextAreaElement>
}

export function Textarea({ className, rows, ...rest }: TextareaProps) {
  return (
    <textarea
      rows={rows ?? 3}
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
