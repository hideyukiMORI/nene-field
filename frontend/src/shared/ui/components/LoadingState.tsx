import { Spinner } from '../primitives/Spinner'

interface LoadingStateProps {
  label: string
}

/** The Loading state of a data screen (frontend-standards §5). */
export function LoadingState({ label }: LoadingStateProps) {
  return (
    <div className="flex items-center justify-center gap-3 py-12 text-fg-muted">
      <Spinner label={label} />
      <span className="text-sm">{label}</span>
    </div>
  )
}
