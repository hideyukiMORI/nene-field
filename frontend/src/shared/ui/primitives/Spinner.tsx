import { cn } from '@/shared/lib/cn'

interface SpinnerProps {
  className?: string
  label?: string
}

export function Spinner({ className, label }: SpinnerProps) {
  return (
    <span
      role="status"
      aria-label={label ?? 'loading'}
      className={cn(
        'inline-block size-4 animate-spin rounded-full border-2 border-border border-t-accent',
        className,
      )}
    />
  )
}
