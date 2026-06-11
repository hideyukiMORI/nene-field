import type { ReportStatus } from '@/entities/report'
import { formatCalendarDate } from '@/shared/lib/format-date'
import { useTranslation } from '@/shared/i18n'
import type { MessageKey } from '@/shared/i18n'
import { Badge, EmptyState, ErrorState, LoadingState } from '@/shared/ui'
import { useListReports } from '../hooks/use-list-reports'

const statusKey: Record<ReportStatus, MessageKey> = {
  draft: 'report.status.draft',
  submitted: 'report.status.submitted',
  approved: 'report.status.approved',
  rejected: 'report.status.rejected',
}

const statusTone = {
  draft: 'neutral',
  submitted: 'info',
  approved: 'success',
  rejected: 'danger',
} as const

export function ListReports() {
  const { t } = useTranslation()
  const { reports, isLoading, isError, refetch } = useListReports()

  if (isLoading) {
    return <LoadingState label={t('common.state.loading')} />
  }

  if (isError) {
    return (
      <ErrorState
        message={t('report.list.error')}
        retryLabel={t('common.actions.retry')}
        onRetry={refetch}
      />
    )
  }

  if (reports.length === 0) {
    return <EmptyState message={t('report.list.empty')} />
  }

  return (
    <div className="overflow-x-auto border border-border bg-surface-raised">
      <table className="w-full text-sm">
        <thead>
          <tr className="border-b border-border-strong text-left text-fg-muted">
            <th scope="col" className="px-3 py-2 font-semibold">
              {t('report.col.workDate')}
            </th>
            <th scope="col" className="px-3 py-2 font-semibold">
              {t('report.col.title')}
            </th>
            <th scope="col" className="px-3 py-2 font-semibold">
              {t('report.col.user')}
            </th>
            <th scope="col" className="px-3 py-2 font-semibold">
              {t('report.col.status')}
            </th>
          </tr>
        </thead>
        <tbody>
          {reports.map((report) => (
            <tr key={report.id} className="border-b border-border">
              <td className="px-3 py-2 text-fg-muted">{formatCalendarDate(report.workDate)}</td>
              <td className="px-3 py-2 text-fg">{report.title}</td>
              <td className="px-3 py-2 text-fg-muted">{report.userName}</td>
              <td className="px-3 py-2">
                <Badge tone={statusTone[report.status]}>{t(statusKey[report.status])}</Badge>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  )
}
