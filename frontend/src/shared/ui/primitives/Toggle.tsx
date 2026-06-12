import { cn } from '@/shared/lib/cn'

interface ToggleProps {
  checked: boolean
  onChange: (next: boolean) => void
  size?: 'md' | 'sm'
  disabled?: boolean
  label?: string
  className?: string
}

/**
 * Switch (design handoff §3.5). on = approved green, off = neutral. The knob
 * slides via a transform transition. `sm` is the compact mobile variant (40×23).
 */
export function Toggle({
  checked,
  onChange,
  size = 'md',
  disabled = false,
  label,
  className,
}: ToggleProps) {
  const track = size === 'md' ? 'h-6 w-11' : 'h-6 w-10'
  const knob = size === 'md' ? 'h-5 w-5' : 'h-4 w-4'
  return (
    <button
      type="button"
      role="switch"
      aria-checked={checked}
      aria-label={label}
      disabled={disabled}
      onClick={() => {
        onChange(!checked)
      }}
      className={cn(
        'relative inline-flex flex-none items-center rounded-pill transition-colors duration-150',
        'disabled:cursor-not-allowed disabled:opacity-50',
        checked ? 'bg-approved' : 'bg-border-strong',
        track,
        className,
      )}
    >
      <span
        className={cn(
          'absolute left-0.5 rounded-full bg-surface-raised shadow-card transition-transform duration-150',
          knob,
          checked ? 'translate-x-5' : 'translate-x-0',
        )}
      />
    </button>
  )
}
