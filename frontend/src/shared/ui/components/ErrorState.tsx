import { Button } from '../primitives/Button'

interface ErrorStateProps {
  message: string
  retryLabel: string
  onRetry: () => void
}

/** The Error state of a data screen — safe message + retry. */
export function ErrorState({ message, retryLabel, onRetry }: ErrorStateProps) {
  return (
    <div className="flex flex-col items-center justify-center gap-4 border border-danger-soft bg-danger-soft py-12">
      <p className="text-sm text-danger">{message}</p>
      <Button variant="secondary" onClick={onRetry}>
        {retryLabel}
      </Button>
    </div>
  )
}
