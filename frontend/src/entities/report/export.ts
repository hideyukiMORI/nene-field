import { apiClient } from '@/shared/api/client'
import type { ReportStatus } from './enum'

export interface ReportExportParams {
  workDateFrom: string
  workDateTo: string
  userId?: string
  projectCode?: string
  statuses: ReportStatus[]
}

/**
 * Downloads the report CSV (admin; UTF-8 with BOM). Statuses are sent as
 * `status[]` so the PHP backend receives an array; the export is audited
 * server-side (`report.exported`, filters only).
 */
export function downloadReportsCsv(params: ReportExportParams): Promise<Blob> {
  const search = new URLSearchParams({
    work_date_from: params.workDateFrom,
    work_date_to: params.workDateTo,
  })
  if (params.userId !== undefined && params.userId !== '') search.set('user_id', params.userId)
  if (params.projectCode !== undefined && params.projectCode !== '') {
    search.set('project_code', params.projectCode)
  }
  for (const status of params.statuses) {
    search.append('status[]', status)
  }
  return apiClient.getBlob(`/export/csv?${search.toString()}`)
}
