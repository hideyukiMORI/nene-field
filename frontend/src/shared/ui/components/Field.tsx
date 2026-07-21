import type { ReactNode } from 'react'

interface FieldProps {
  label: string
  htmlFor: string
  error?: string | undefined
  children: ReactNode
}

/** A labelled form control with an accessible error association. */
export function Field({ label, htmlFor, error, children }: FieldProps) {
  const errorId = `${htmlFor}-error`
  return (
    <div className="flex flex-col gap-1">
      <label htmlFor={htmlFor} className="text-sm font-medium text-text-primary">
        {label}
      </label>
      {children}
      {error !== undefined && (
        <p id={errorId} className="text-xs text-danger">
          {error}
        </p>
      )}
    </div>
  )
}
