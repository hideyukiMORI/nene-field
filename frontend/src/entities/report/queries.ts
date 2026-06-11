import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient } from '@/shared/api/client'
import type { AppError } from '@/shared/api/errors'
import type { ReportListResponseDto, ReportResponseDto } from './api-types'
import { toReportDetail, toReportList } from './mapper'
import type { ReportDetail, ReportList } from './model'
import { reportKeys, type ReportListParams } from './query-keys'

function buildQuery(params: ReportListParams): string {
  const search = new URLSearchParams()
  if (params.status !== undefined) search.set('status', params.status)
  if (params.userId !== undefined) search.set('user_id', params.userId)
  search.set('limit', String(params.limit))
  search.set('offset', String(params.offset))
  return search.toString()
}

export function useReportListQuery(params: ReportListParams): UseQueryResult<ReportList, AppError> {
  return useQuery<ReportList, AppError>({
    queryKey: reportKeys.list(params),
    queryFn: async () => {
      const dto = await apiClient.get<ReportListResponseDto>(`/reports?${buildQuery(params)}`)
      return toReportList(dto)
    },
  })
}

export function useReportQuery(id: string): UseQueryResult<ReportDetail, AppError> {
  return useQuery<ReportDetail, AppError>({
    queryKey: reportKeys.detail(id),
    queryFn: async () => {
      const dto = await apiClient.get<ReportResponseDto>(`/reports/${encodeURIComponent(id)}`)
      return toReportDetail(dto)
    },
  })
}
