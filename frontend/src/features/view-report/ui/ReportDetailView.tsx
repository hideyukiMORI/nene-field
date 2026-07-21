import type { ReactNode } from 'react'
import type { ReportDetail, ReportStatus } from '@/entities/report'
import { useTranslation } from '@/shared/i18n'
import type { MessageKey } from '@/shared/i18n'
import { formatCalendarDate, formatJstDateTime } from '@/shared/lib/format-date'
import {
  Badge,
  Button,
  EmptyState,
  ErrorState,
  InlineAlert,
  LoadingState,
  Stack,
  Text,
} from '@/shared/ui'
import { useDownloadAttachment } from '../model/use-download-attachment'
import { useReportDetail } from '../model/use-report-detail'

const statusKey: Record<ReportStatus, MessageKey> = {
  draft: 'report.status.draft',
  submitted: 'report.status.submitted',
  approved: 'report.status.approved',
  rejected: 'report.status.rejected',
}

const statusTone = {
  draft: 'draft',
  submitted: 'submitted',
  approved: 'approved',
  rejected: 'rejected',
} as const

interface ReportDetailViewProps {
  reportId: string
  renderActions?: ((report: ReportDetail) => ReactNode) | undefined
}

export function ReportDetailView({ reportId, renderActions }: ReportDetailViewProps) {
  const { t } = useTranslation()
  const { report, isLoading, isError, isNotFound, refetch } = useReportDetail(reportId)
  const downloads = useDownloadAttachment(reportId)

  if (isLoading) {
    return <LoadingState label={t('common.state.loading')} />
  }

  if (isNotFound) {
    return <EmptyState message={t('report.detail.notFound')} />
  }

  if (isError || report === undefined) {
    return (
      <ErrorState
        message={t('report.detail.error')}
        retryLabel={t('common.actions.retry')}
        onRetry={refetch}
      />
    )
  }

  return (
    <Stack gap="lg">
      <Stack gap="sm">
        <div className="flex items-center gap-3">
          <Badge tone={statusTone[report.status]}>{t(statusKey[report.status])}</Badge>
          <Text variant="title" as="h2">
            {report.title}
          </Text>
        </div>
      </Stack>

      <dl className="grid grid-cols-2 gap-3 border border-border bg-surface-raised p-4 text-sm">
        <Meta label={t('report.field.user')} value={report.userName} />
        <Meta label={t('report.field.workDate')} value={formatCalendarDate(report.workDate)} />
        {report.projectCode !== null && (
          <Meta label={t('report.field.projectCode')} value={report.projectCode} />
        )}
        {report.submittedAt !== null && (
          <Meta
            label={t('report.field.submittedAt')}
            value={formatJstDateTime(report.submittedAt)}
          />
        )}
        {report.approvedAt !== null && (
          <Meta label={t('report.field.approvedAt')} value={formatJstDateTime(report.approvedAt)} />
        )}
        {report.rejectedAt !== null && (
          <Meta label={t('report.field.rejectedAt')} value={formatJstDateTime(report.rejectedAt)} />
        )}
      </dl>

      {report.approverComment !== null && (
        <InlineAlert variant="warn">
          {t('report.field.approverComment')}: {report.approverComment}
        </InlineAlert>
      )}

      <Stack gap="sm">
        <Text variant="subtitle">{t('report.field.body')}</Text>
        <p className="border border-border bg-surface-raised p-4 text-sm whitespace-pre-wrap text-text-primary">
          {report.body}
        </p>
      </Stack>

      {report.aiSummary !== null && (
        <div className="rounded-x-card bg-x-ai-soft p-4">
          <span className="text-xs font-semibold text-x-ai">{t('report.field.aiSummary')}</span>
          <p className="mt-1 text-sm text-text-primary">{report.aiSummary}</p>
        </div>
      )}

      <Stack gap="sm">
        <Text variant="subtitle">{t('report.attachment.title')}</Text>
        {downloads.errorKey !== null && (
          <InlineAlert variant="error">{t(downloads.errorKey)}</InlineAlert>
        )}
        {report.attachments.length === 0 ? (
          <Text variant="muted">{t('report.attachment.none')}</Text>
        ) : (
          <ul className="divide-y divide-border border border-border bg-surface-raised">
            {report.attachments.map((attachment) => (
              <li
                key={attachment.attachmentId}
                className="flex items-center justify-between gap-3 px-4 py-2"
              >
                <span className="text-sm text-text-primary">
                  {attachment.filename}
                  <span className="ml-2 text-text-muted">{formatFileSize(attachment.fileSize)}</span>
                </span>
                <Button
                  variant="secondary"
                  disabled={downloads.busyId === attachment.attachmentId}
                  onClick={() => {
                    downloads.download(attachment.attachmentId, attachment.filename)
                  }}
                >
                  {t('common.actions.download')}
                </Button>
              </li>
            ))}
          </ul>
        )}
      </Stack>

      {renderActions?.(report)}
    </Stack>
  )
}

function Meta({ label, value }: { label: string; value: string }) {
  return (
    <div className="flex flex-col gap-0.5">
      <dt className="text-text-muted">{label}</dt>
      <dd className="text-text-primary">{value}</dd>
    </div>
  )
}

function formatFileSize(bytes: number): string {
  if (bytes < 1024) return `${String(bytes)} B`
  return `${String(Math.round(bytes / 1024))} KB`
}
