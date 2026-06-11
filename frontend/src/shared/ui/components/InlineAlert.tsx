import type { ReactNode } from 'react'
import { cn } from '@/shared/lib/cn'

type AlertVariant = 'error' | 'success' | 'info' | 'warn'

interface InlineAlertProps {
  variant?: AlertVariant
  children: ReactNode
}

const variantClass: Record<AlertVariant, string> = {
  error: 'bg-danger-soft text-danger border-danger',
  success: 'bg-success-soft text-success border-success',
  info: 'bg-info-soft text-info border-info',
  warn: 'bg-warn-soft text-warn border-warn',
}

export function InlineAlert({ variant = 'info', children }: InlineAlertProps) {
  return (
    <div role="alert" className={cn('border px-3 py-2 text-sm', variantClass[variant])}>
      {children}
    </div>
  )
}
