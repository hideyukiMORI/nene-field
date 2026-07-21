import type { ButtonHTMLAttributes, ReactNode } from 'react'
import { cn } from '@/shared/lib/cn'

/**
 * Variants (design handoff §3.1). All pill-shaped with a subtle press scale.
 * - primary        : accent fill, accent shadow (主要アクション / CTA)
 * - success         : green fill (承認)
 * - danger          : solid red fill
 * - danger-ghost    : white, red text + border (差戻し)
 * - secondary/ghost : white, neutral text + border
 */
type ButtonVariant = 'primary' | 'success' | 'danger' | 'danger-ghost' | 'secondary' | 'ghost'
type ButtonSize = 'md' | 'sm' | 'lg'

interface ButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: ButtonVariant
  size?: ButtonSize
  children: ReactNode
}

const variantClass: Record<ButtonVariant, string> = {
  primary: 'bg-accent text-fg-inverse border-accent shadow-btn hover:bg-accent-hover',
  success:
    'bg-btn-success text-fg-inverse border-btn-success shadow-btn-success hover:brightness-105',
  danger: 'bg-danger text-fg-inverse border-danger hover:brightness-105',
  'danger-ghost': 'bg-surface-raised text-rejected border-rejected/40 hover:bg-rejected-soft',
  secondary: 'bg-surface-raised text-fg border-border-strong hover:bg-surface-overlay',
  ghost: 'bg-surface-raised text-fg border-border-strong hover:bg-surface-overlay',
}

const sizeClass: Record<ButtonSize, string> = {
  sm: 'px-3.5 py-1.5 text-xs',
  md: 'px-5 py-2.5 text-sm',
  lg: 'px-6 py-3.5 text-base',
}

export function Button({
  variant = 'primary',
  size = 'md',
  className,
  type,
  children,
  ...rest
}: ButtonProps) {
  return (
    <button
      type={type ?? 'button'}
      className={cn(
        'inline-flex items-center justify-center gap-2 rounded-pill border font-semibold',
        'transition-transform duration-100 active:scale-95',
        'disabled:cursor-not-allowed disabled:opacity-50 disabled:active:scale-100',
        sizeClass[size],
        variantClass[variant],
        className,
      )}
      {...rest}
    >
      {children}
    </button>
  )
}
