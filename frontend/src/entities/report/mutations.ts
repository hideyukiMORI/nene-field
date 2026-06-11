import { useMutation, useQueryClient, type UseMutationResult } from '@tanstack/react-query'
import { apiClient } from '@/shared/api/client'
import type { AppError } from '@/shared/api/errors'
import type { ReportResponseDto } from './api-types'
import { toReportDetail } from './mapper'
import type { ReportDetail } from './model'
import { reportKeys } from './query-keys'

export interface ApproveReportInput {
  reportId: string
  comment?: string
}

export interface RejectReportInput {
  reportId: string
  comment: string
}

function useInvalidateReport(): (id: string) => void {
  const queryClient = useQueryClient()
  return (id) => {
    void queryClient.invalidateQueries({ queryKey: reportKeys.detail(id) })
    void queryClient.invalidateQueries({ queryKey: reportKeys.all })
  }
}

export function useApproveReportMutation(): UseMutationResult<
  ReportDetail,
  AppError,
  ApproveReportInput
> {
  const invalidate = useInvalidateReport()
  return useMutation<ReportDetail, AppError, ApproveReportInput>({
    mutationFn: async ({ reportId, comment }) => {
      const body = comment !== undefined ? { comment } : {}
      const dto = await apiClient.post<ReportResponseDto>(
        `/reports/${encodeURIComponent(reportId)}/approve`,
        body,
      )
      return toReportDetail(dto)
    },
    onSuccess: (report) => {
      invalidate(report.id)
    },
  })
}

export function useRejectReportMutation(): UseMutationResult<
  ReportDetail,
  AppError,
  RejectReportInput
> {
  const invalidate = useInvalidateReport()
  return useMutation<ReportDetail, AppError, RejectReportInput>({
    mutationFn: async ({ reportId, comment }) => {
      const dto = await apiClient.post<ReportResponseDto>(
        `/reports/${encodeURIComponent(reportId)}/reject`,
        { comment },
      )
      return toReportDetail(dto)
    },
    onSuccess: (report) => {
      invalidate(report.id)
    },
  })
}
