/** Wire DTOs (snake_case) for the report list — see docs/openapi/openapi.yaml. */

export interface ReportSummaryDto {
  report_id: string
  user_id: string
  user_name: string
  title: string
  work_date: string
  status: string
  tags?: string[]
  project_code?: string | null
  ai_summary?: string | null
  submitted_at?: string | null
  created_at: string
}

export interface ReportListResponseDto {
  items: ReportSummaryDto[]
  limit: number
  offset: number
  total?: number
}
