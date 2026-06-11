import type { ReportStatus } from './enum'

export interface ReportListParams {
  status?: ReportStatus
  userId?: string
  limit: number
  offset: number
}

export const reportKeys = {
  all: ['reports'] as const,
  list: (params: ReportListParams) => ['reports', 'list', params] as const,
}
