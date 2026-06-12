import { cn } from '@/shared/lib/cn'

type DonutTone = 'approved' | 'submitted' | 'rejected' | 'draft'

export interface DonutSegment {
  tone: DonutTone
  value: number
  label: string
}

const toneVar: Record<DonutTone, string> = {
  approved: 'var(--color-approved)',
  submitted: 'var(--color-submitted)',
  rejected: 'var(--color-rejected)',
  draft: 'var(--color-draft)',
}

const toneDot: Record<DonutTone, string> = {
  approved: 'bg-approved',
  submitted: 'bg-submitted',
  rejected: 'bg-rejected',
  draft: 'bg-draft',
}

/**
 * CSS-only donut chart (design handoff §3.11): a conic-gradient ring with a
 * white center hole showing the total, plus a token-colored legend.
 */
export function Donut({ segments }: { segments: DonutSegment[] }) {
  const total = segments.reduce((sum, s) => sum + s.value, 0)
  const stops = segments
    .map((s, i) => {
      const before = segments.slice(0, i).reduce((sum, x) => sum + x.value, 0)
      const start = total === 0 ? 0 : (before / total) * 100
      const end = total === 0 ? 0 : ((before + s.value) / total) * 100
      return `${toneVar[s.tone]} ${String(start)}% ${String(end)}%`
    })
    .join(', ')

  return (
    <div className="flex items-center gap-5">
      <div
        className="relative grid h-32 w-32 place-items-center rounded-full"
        style={{ background: `conic-gradient(${stops})` }}
      >
        <div className="grid h-20 w-20 place-items-center rounded-full bg-surface-raised">
          <span className="text-xl font-bold text-fg tnum">{total}</span>
        </div>
      </div>
      <ul className="flex flex-col gap-2">
        {segments.map((s) => (
          <li key={s.tone} className="flex items-center gap-2 text-sm">
            <span className={cn('h-2.5 w-2.5 rounded-full', toneDot[s.tone])} />
            <span className="text-fg-muted">{s.label}</span>
            <span className="font-semibold text-fg tnum">{s.value}</span>
          </li>
        ))}
      </ul>
    </div>
  )
}
