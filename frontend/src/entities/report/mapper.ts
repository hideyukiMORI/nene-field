import type { ReportListResponseDto, ReportSummaryDto } from './api-types'
import { isReportStatus, type ReportStatus } from './enum'
import { toReportId } from './ids'
import type { ReportList, ReportSummary } from './model'

function toStatus(value: string): ReportStatus {
  return isReportStatus(value) ? value : 'draft'
}

export function toReportSummary(dto: ReportSummaryDto): ReportSummary {
  return {
    id: toReportId(dto.report_id),
    userId: dto.user_id,
    userName: dto.user_name,
    title: dto.title,
    workDate: dto.work_date,
    status: toStatus(dto.status),
    tags: dto.tags ?? [],
    projectCode: dto.project_code ?? null,
    aiSummary: dto.ai_summary ?? null,
    submittedAt: dto.submitted_at ?? null,
    createdAt: dto.created_at,
  }
}

export function toReportList(dto: ReportListResponseDto): ReportList {
  return {
    items: dto.items.map(toReportSummary),
    limit: dto.limit,
    offset: dto.offset,
    total: dto.total ?? dto.items.length,
  }
}
