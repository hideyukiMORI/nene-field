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

export interface AttachmentSummaryDto {
  attachment_id: string
  filename: string
  mime_type: string
  file_size: number
  sha256: string
  created_at: string
}

export interface ReportResponseDto {
  report_id: string
  organization_id: string
  user_id: string
  user_name?: string
  template_id?: string | null
  title: string
  body: string
  work_date: string
  status: string
  tags?: string[]
  project_code?: string | null
  ai_summary?: string | null
  ai_tags?: string[] | null
  submitted_at?: string | null
  approved_at?: string | null
  rejected_at?: string | null
  approver_id?: string | null
  approver_comment?: string | null
  attachments?: AttachmentSummaryDto[]
  created_at: string
  updated_at: string
}
