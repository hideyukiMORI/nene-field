import { cn } from '@/shared/lib/cn'

interface SpinnerProps {
  className?: string
  /** aria-label for the spinner. I18N-18: pass t('common.state.loading'). */
  label: string
}

export function Spinner({ className, label }: SpinnerProps) {
  return (
    <span
      role="status"
      aria-label={label}
      className={cn(
        'inline-block size-4 animate-spin rounded-full border-2 border-border border-t-accent',
        className,
      )}
    />
  )
}
