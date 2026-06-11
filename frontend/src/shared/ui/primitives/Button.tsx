import type { ButtonHTMLAttributes, ReactNode } from 'react'
import { cn } from '@/shared/lib/cn'

type ButtonVariant = 'primary' | 'secondary' | 'danger'

interface ButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: ButtonVariant
  children: ReactNode
}

const variantClass: Record<ButtonVariant, string> = {
  primary: 'bg-accent text-fg-inverse hover:bg-accent-hover border-accent',
  secondary: 'bg-surface-raised text-fg hover:bg-surface-overlay border-border-strong',
  danger: 'bg-danger text-fg-inverse border-danger',
}

export function Button({ variant = 'primary', className, type, children, ...rest }: ButtonProps) {
  return (
    <button
      type={type ?? 'button'}
      className={cn(
        'inline-flex items-center justify-center gap-2 border px-3 py-2 text-sm font-semibold transition-colors disabled:cursor-not-allowed disabled:opacity-50',
        variantClass[variant],
        className,
      )}
      {...rest}
    >
      {children}
    </button>
  )
}
