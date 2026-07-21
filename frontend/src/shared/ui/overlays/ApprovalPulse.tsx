import { useEffect } from 'react'

interface ApprovalPulseProps {
  /** A monotonically increasing trigger; bumping it replays the pulse. 0 = idle. */
  trigger: number
  onDone: () => void
}

/**
 * Approval micro-animation (design handoff §3.11 / §4): a green check circle
 * (nfcheckpop) with an expanding ring (nfring), the whole thing fading over
 * ~1.15s (nffade). Re-keyed on `trigger` so each approval replays the CSS animation.
 */
export function ApprovalPulse({ trigger, onDone }: ApprovalPulseProps) {
  useEffect(() => {
    if (trigger === 0) return
    const t = setTimeout(onDone, 1150)
    return () => {
      clearTimeout(t)
    }
  }, [trigger, onDone])

  if (trigger === 0) return null

  return (
    <div
      key={trigger}
      className="pointer-events-none fixed inset-0 z-50 grid place-items-center animate-nffade"
      aria-hidden
    >
      <div className="relative grid place-items-center">
        <span className="absolute h-28 w-28 rounded-full border-4 border-x-approved animate-nfring" />
        <span className="grid h-24 w-24 place-items-center rounded-full bg-x-approved text-5xl text-text-inverse shadow-x-card animate-nfcheckpop">
          ✓
        </span>
      </div>
    </div>
  )
}
