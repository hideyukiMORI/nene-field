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
        'block w-full rounded-x-input border border-x-border-input bg-surface-raised px-3 py-2.5 text-sm leading-relaxed text-text-primary outline-none',
        'placeholder:text-text-faint focus:border-accent focus:ring-2 focus:ring-accent-soft',
        'disabled:cursor-not-allowed disabled:opacity-50',
        className,
      )}
      {...rest}
    />
  )
}
