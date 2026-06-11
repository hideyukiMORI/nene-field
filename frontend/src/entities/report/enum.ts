export const REPORT_STATUSES = ['draft', 'submitted', 'approved', 'rejected'] as const

export type ReportStatus = (typeof REPORT_STATUSES)[number]

export function isReportStatus(value: string): value is ReportStatus {
  return (REPORT_STATUSES as readonly string[]).includes(value)
}
