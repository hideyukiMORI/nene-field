import { cn } from '@/shared/lib/cn'

export interface BarDatum {
  label: string
  value: number
  /** Today's bar is rendered in the lighter accent tint. */
  today?: boolean
}

/**
 * CSS-only bar chart (design handoff §3.11). Bar heights are a percentage of the
 * max value over a 128px basis; today's column uses the soft accent tint.
 */
export function BarChart({ data }: { data: BarDatum[] }) {
  const max = Math.max(1, ...data.map((d) => d.value))
  return (
    <div className="flex h-40 items-end gap-3.5">
      {data.map((d) => (
        <div key={d.label} className="flex flex-1 flex-col items-center justify-end gap-1.5">
          <span className="text-xs font-semibold text-text-primary tnum">{d.value}</span>
          <div
            className={cn('w-full rounded-t', d.today ? 'bg-x-accent-soft-border' : 'bg-accent')}
            style={{ height: `${String(Math.round((d.value / max) * 128))}px` }}
          />
          <span className="text-xs text-text-faint">{d.label}</span>
        </div>
      ))}
    </div>
  )
}
