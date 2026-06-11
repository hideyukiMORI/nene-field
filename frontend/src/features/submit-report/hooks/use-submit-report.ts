import {
  useCreateReportMutation,
  useSubmitReportMutation,
  type CreateReportInput,
} from '@/entities/report'
import type { MessageKey } from '@/shared/i18n'

export interface SubmitReport {
  saveDraft: (input: CreateReportInput) => void
  submit: (input: CreateReportInput) => void
  isPending: boolean
  errorKey: MessageKey | null
}

/**
 * Submission workflow. `saveDraft` creates a draft; `submit` creates the draft
 * then submits it for approval. `onDone` receives the new report id.
 */
export function useSubmitReport(onDone: (reportId: string) => void): SubmitReport {
  const createMutation = useCreateReportMutation()
  const submitMutation = useSubmitReportMutation()

  const errored = createMutation.error !== null || submitMutation.error !== null

  return {
    saveDraft: (input) => {
      createMutation.mutate(input, {
        onSuccess: (report) => {
          onDone(report.id)
        },
      })
    },
    submit: (input) => {
      createMutation.mutate(input, {
        onSuccess: (report) => {
          submitMutation.mutate(report.id, {
            onSuccess: () => {
              onDone(report.id)
            },
          })
        },
      })
    },
    isPending: createMutation.isPending || submitMutation.isPending,
    errorKey: errored ? 'report.submit.error' : null,
  }
}
