import type { ReportStatus } from './enum'
import type { ReportId } from './ids'

/** Condensed report for list views (UI model). */
export interface ReportSummary {
  id: ReportId
  userId: string
  userName: string
  title: string
  workDate: string
  status: ReportStatus
  tags: string[]
  projectCode: string | null
  aiSummary: string | null
  submittedAt: string | null
  createdAt: string
}

export interface ReportList {
  items: ReportSummary[]
  limit: number
  offset: number
  total: number
}

/** An attachment as embedded in the report detail (download via report-attachment). */
export interface ReportAttachmentSummary {
  attachmentId: string
  filename: string
  mimeType: string
  fileSize: number
  sha256: string
  createdAt: string
}

/** Full report for the detail view (UI model). */
export interface ReportDetail {
  id: ReportId
  organizationId: string
  userId: string
  userName: string
  title: string
  body: string
  workDate: string
  status: ReportStatus
  tags: string[]
  projectCode: string | null
  aiSummary: string | null
  submittedAt: string | null
  approvedAt: string | null
  rejectedAt: string | null
  approverId: string | null
  approverComment: string | null
  attachments: ReportAttachmentSummary[]
  createdAt: string
  updatedAt: string
}
