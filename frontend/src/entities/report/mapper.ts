import type {
  AttachmentSummaryDto,
  ReportListResponseDto,
  ReportResponseDto,
  ReportSummaryDto,
} from './api-types'
import { isReportStatus, type ReportStatus } from './enum'
import { toReportId } from './ids'
import type { ReportAttachmentSummary, ReportDetail, ReportList, ReportSummary } from './model'

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

function toAttachmentSummary(dto: AttachmentSummaryDto): ReportAttachmentSummary {
  return {
    attachmentId: dto.attachment_id,
    filename: dto.filename,
    mimeType: dto.mime_type,
    fileSize: dto.file_size,
    sha256: dto.sha256,
    createdAt: dto.created_at,
  }
}

export function toReportDetail(dto: ReportResponseDto): ReportDetail {
  return {
    id: toReportId(dto.report_id),
    organizationId: dto.organization_id,
    userId: dto.user_id,
    userName: dto.user_name ?? '',
    title: dto.title,
    body: dto.body,
    workDate: dto.work_date,
    status: toStatus(dto.status),
    tags: dto.tags ?? [],
    projectCode: dto.project_code ?? null,
    aiSummary: dto.ai_summary ?? null,
    submittedAt: dto.submitted_at ?? null,
    approvedAt: dto.approved_at ?? null,
    rejectedAt: dto.rejected_at ?? null,
    approverId: dto.approver_id ?? null,
    approverComment: dto.approver_comment ?? null,
    attachments: (dto.attachments ?? []).map(toAttachmentSummary),
    createdAt: dto.created_at,
    updatedAt: dto.updated_at,
  }
}
