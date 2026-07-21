import { cn } from '@/shared/lib/cn'

interface CheckboxProps {
  checked: boolean
  onChange: (next: boolean) => void
  indeterminate?: boolean
  disabled?: boolean
  label?: string
  className?: string
}

/**
 * Selection checkbox (design handoff §3.5). 18px, rounded-5. Checked = accent
 * fill with a white tick; supports an indeterminate (partial) state for
 * select-all table headers.
 */
export function Checkbox({
  checked,
  onChange,
  indeterminate = false,
  disabled = false,
  label,
  className,
}: CheckboxProps) {
  const active = checked || indeterminate
  return (
    <button
      type="button"
      role="checkbox"
      aria-checked={indeterminate ? 'mixed' : checked}
      aria-label={label}
      disabled={disabled}
      onClick={() => {
        onChange(!checked)
      }}
      className={cn(
        'inline-grid h-5 w-5 flex-none place-items-center rounded border text-xs leading-none',
        'transition-colors disabled:cursor-not-allowed disabled:opacity-50',
        active
          ? 'border-accent bg-accent text-text-inverse'
          : 'border-border-strong bg-surface-raised text-transparent',
        className,
      )}
    >
      {indeterminate ? '–' : '✓'}
    </button>
  )
}
