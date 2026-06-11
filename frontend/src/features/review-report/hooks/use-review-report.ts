import { useApproveReportMutation, useRejectReportMutation } from '@/entities/report'
import type { MessageKey } from '@/shared/i18n'

export interface ReviewReport {
  approve: (comment?: string) => void
  reject: (comment: string) => void
  isPending: boolean
  errorKey: MessageKey | null
}

/**
 * Review workflow for a submitted report: approve (optional comment) or send it
 * back (required comment). Both invalidate the report detail + list caches.
 */
export function useReviewReport(reportId: string): ReviewReport {
  const approveMutation = useApproveReportMutation()
  const rejectMutation = useRejectReportMutation()

  const errored = approveMutation.error !== null || rejectMutation.error !== null

  return {
    approve: (comment) => {
      approveMutation.mutate({ reportId, ...(comment !== undefined ? { comment } : {}) })
    },
    reject: (comment) => {
      rejectMutation.mutate({ reportId, comment })
    },
    isPending: approveMutation.isPending || rejectMutation.isPending,
    errorKey: errored ? 'report.review.error' : null,
  }
}
