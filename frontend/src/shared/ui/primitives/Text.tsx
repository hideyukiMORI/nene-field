import type { ReactNode } from 'react'
import { cn } from '@/shared/lib/cn'

type TextVariant = 'title' | 'subtitle' | 'body' | 'muted'

interface TextProps {
  variant?: TextVariant
  as?: 'p' | 'span' | 'h1' | 'h2' | 'h3'
  className?: string
  children: ReactNode
}

const variantClass: Record<TextVariant, string> = {
  title: 'text-xl font-bold text-fg',
  subtitle: 'text-sm text-fg-muted',
  body: 'text-sm text-fg',
  muted: 'text-sm text-fg-muted',
}

export function Text({ variant = 'body', as = 'p', className, children }: TextProps) {
  const Tag = as
  return <Tag className={cn(variantClass[variant], className)}>{children}</Tag>
}
