import { useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import { useReportListQuery, type ReportStatus } from '@/entities/report'
import { useTranslation } from '@/shared/i18n'
import type { MessageKey } from '@/shared/i18n'
import { formatCalendarDate } from '@/shared/lib/format-date'
import { Badge, Chip, EmptyState, ErrorState, LoadingState } from '@/shared/ui'

type Filter = 'all' | 'rejected' | 'draft'

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

export function MobileReportsPage() {
  const { t } = useTranslation()
  const query = useReportListQuery({ limit: 50, offset: 0 })
  const reports = useMemo(() => query.data?.items ?? [], [query.data])
  const isLoading = query.isLoading
  const isError = query.isError
  const refetch = (): void => {
    void query.refetch()
  }
  const [filter, setFilter] = useState<Filter>('all')

  const filtered = useMemo(() => {
    if (filter === 'all') return reports
    return reports.filter((r) => r.status === filter)
  }, [reports, filter])

  const rejectedCount = reports.filter((r) => r.status === 'rejected').length
  const draftCount = reports.filter((r) => r.status === 'draft').length

  return (
    <div className="flex flex-col">
      <header className="border-b border-border bg-surface-raised px-4 py-3.5">
        <h1 className="text-base font-bold text-fg">{t('mobile.tab.reports')}</h1>
      </header>

      <div className="flex gap-1.5 overflow-x-auto px-4 py-3">
        <Chip
          active={filter === 'all'}
          onClick={() => {
            setFilter('all')
          }}
        >
          {t('mobile.reports.filter.all')}
        </Chip>
        <Chip
          active={filter === 'rejected'}
          onClick={() => {
            setFilter('rejected')
          }}
        >
          {t('mobile.reports.filter.rejected')} {rejectedCount}
        </Chip>
        <Chip
          active={filter === 'draft'}
          onClick={() => {
            setFilter('draft')
          }}
        >
          {t('mobile.reports.filter.draft')} {draftCount}
        </Chip>
      </div>

      <div className="flex flex-col gap-2.5 px-4 pb-4">
        {isLoading ? (
          <LoadingState label={t('common.state.loading')} />
        ) : isError ? (
          <ErrorState
            message={t('report.list.error')}
            retryLabel={t('common.actions.retry')}
            onRetry={refetch}
          />
        ) : filtered.length === 0 ? (
          <EmptyState message={t('report.list.empty')} />
        ) : (
          filtered.map((r) => (
            <Link
              key={r.id}
              to={`/reports/${r.id}`}
              className={
                r.status === 'rejected'
                  ? 'rounded-card border border-border border-l-4 border-l-rejected bg-surface-raised p-3.5'
                  : 'rounded-card border border-border bg-surface-raised p-3.5'
              }
            >
              <div className="flex items-center justify-between gap-2">
                <span className="truncate font-semibold text-fg">{r.title}</span>
                <Badge tone={statusTone[r.status]}>{t(statusKey[r.status])}</Badge>
              </div>
              <div className="mt-1 flex flex-wrap items-center gap-2 text-xs text-fg-muted">
                <span className="font-mono tnum">{formatCalendarDate(r.workDate)}</span>
                {r.tags.slice(0, 2).map((tag) => (
                  <Chip key={tag}>{tag}</Chip>
                ))}
              </div>
              {r.aiSummary !== null && (
                <p className="mt-1.5 line-clamp-2 text-xs text-fg-faint">{r.aiSummary}</p>
              )}
            </Link>
          ))
        )}
      </div>
    </div>
  )
}
