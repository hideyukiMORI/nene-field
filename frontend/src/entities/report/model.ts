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
