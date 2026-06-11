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

export interface CreateReportInput {
  title: string
  body: string
  workDate: string
  tags?: string[]
  projectCode?: string | null
  templateId?: string | null
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

function toCreateBody(input: CreateReportInput): Record<string, unknown> {
  const body: Record<string, unknown> = {
    title: input.title,
    body: input.body,
    work_date: input.workDate,
  }
  if (input.tags !== undefined) body['tags'] = input.tags
  if (input.projectCode !== undefined && input.projectCode !== null) {
    body['project_code'] = input.projectCode
  }
  if (input.templateId !== undefined && input.templateId !== null) {
    body['template_id'] = input.templateId
  }
  return body
}

export function useCreateReportMutation(): UseMutationResult<
  ReportDetail,
  AppError,
  CreateReportInput
> {
  const queryClient = useQueryClient()
  return useMutation<ReportDetail, AppError, CreateReportInput>({
    mutationFn: async (input) => {
      const dto = await apiClient.post<ReportResponseDto>('/reports', toCreateBody(input))
      return toReportDetail(dto)
    },
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: reportKeys.all })
    },
  })
}

export function useSubmitReportMutation(): UseMutationResult<ReportDetail, AppError, string> {
  const invalidate = useInvalidateReport()
  return useMutation<ReportDetail, AppError, string>({
    mutationFn: async (reportId) => {
      const dto = await apiClient.post<ReportResponseDto>(
        `/reports/${encodeURIComponent(reportId)}/submit`,
      )
      return toReportDetail(dto)
    },
    onSuccess: (report) => {
      invalidate(report.id)
    },
  })
}
