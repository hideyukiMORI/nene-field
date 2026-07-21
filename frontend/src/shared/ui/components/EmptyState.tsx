interface EmptyStateProps {
  message: string
}

/** The Empty state of a data screen — intentional copy, never a blank panel. */
export function EmptyState({ message }: EmptyStateProps) {
  return (
    <div className="flex items-center justify-center border border-dashed border-border py-12 text-sm text-text-muted">
      {message}
    </div>
  )
}
