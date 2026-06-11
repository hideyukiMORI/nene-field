import type { ReactNode } from 'react'
import { cn } from '@/shared/lib/cn'

type Gap = 'sm' | 'md' | 'lg'

interface StackProps {
  direction?: 'row' | 'col'
  gap?: Gap
  className?: string
  children: ReactNode
}

const gapClass: Record<Gap, string> = {
  sm: 'gap-2',
  md: 'gap-4',
  lg: 'gap-6',
}

export function Stack({ direction = 'col', gap = 'md', className, children }: StackProps) {
  return (
    <div
      className={cn(
        'flex',
        direction === 'col' ? 'flex-col' : 'flex-row',
        gapClass[gap],
        className,
      )}
    >
      {children}
    </div>
  )
}
