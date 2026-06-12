import { useSyncExternalStore } from 'react'
import { Link } from 'react-router-dom'
import { getCurrentUser, subscribeCurrentUser } from '@/entities/auth'
import { useReportListQuery, type ReportStatus } from '@/entities/report'
import { useTranslation } from '@/shared/i18n'
import type { MessageKey } from '@/shared/i18n'
import { formatCalendarDate } from '@/shared/lib/format-date'
import { Badge } from '@/shared/ui'

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

function todayIso(): string {
  return new Date().toISOString().slice(0, 10)
}

export function MobileHomePage() {
  const { t } = useTranslation()
  const user = useSyncExternalStore(subscribeCurrentUser, getCurrentUser)
  const reportsQuery = useReportListQuery({ limit: 20, offset: 0 })
  const reports = reportsQuery.data?.items ?? []

  const today = todayIso()
  const todays = reports.find((r) => r.workDate === today)
  const recent = reports.slice(0, 5)

  return (
    <div className="flex flex-col">
      {/* gradient header */}
      <header className="bg-gradient-to-br from-accent to-accent-deep px-4 pb-6 pt-6 text-fg-inverse">
        <div className="flex items-center justify-between">
          <span className="text-lg font-extrabold tracking-wide">{t('common.app.name')}</span>
          <div className="flex items-center gap-2">
            <Link
              to="/notifications"
              aria-label={t('shell.notifications.title')}
              className="grid h-9 w-9 place-items-center rounded-pill bg-white/15 text-base"
            >
              ◔
            </Link>
            <span className="grid h-9 w-9 place-items-center rounded-pill bg-white/15 text-sm font-bold">
              {user?.name.slice(0, 2) ?? '??'}
            </span>
          </div>
        </div>
        <p className="mt-4 text-sm text-fg-inverse/80">{t('mobile.home.greeting')}</p>
        <p className="text-xl font-bold">{user?.name ?? ''}</p>
      </header>

      <div className="flex flex-col gap-4 p-4">
        {/* today status */}
        <div className="rounded-card border border-border bg-surface-raised p-4 shadow-card">
          <p className="text-xs text-fg-muted">{t('mobile.home.todayStatus')}</p>
          <div className="mt-1.5 flex items-center justify-between">
            <span className="font-mono text-sm text-fg tnum">{today}</span>
            {todays === undefined ? (
              <Badge tone="warn">{t('mobile.home.notSubmitted')}</Badge>
            ) : (
              <Badge tone={statusTone[todays.status]}>{t(statusKey[todays.status])}</Badge>
            )}
          </div>
        </div>

        {/* big CTA */}
        <Link
          to="/reports/new"
          className="grid place-items-center rounded-pill bg-accent py-4 text-base font-bold text-fg-inverse shadow-btn active:scale-95"
        >
          ＋ {t('mobile.home.cta')}
        </Link>

        {/* recent */}
        <div>
          <h2 className="mb-2 text-sm font-bold text-fg">{t('mobile.home.recent')}</h2>
          <div className="flex flex-col gap-2.5">
            {recent.map((r) => (
              <Link
                key={r.id}
                to={`/reports/${r.id}`}
                className="rounded-card border border-border bg-surface-raised p-3.5"
              >
                <div className="flex items-center justify-between gap-2">
                  <span className="truncate font-semibold text-fg">{r.title}</span>
                  <Badge tone={statusTone[r.status]}>{t(statusKey[r.status])}</Badge>
                </div>
                <p className="mt-1 font-mono text-xs text-fg-muted tnum">
                  {formatCalendarDate(r.workDate)}
                </p>
              </Link>
            ))}
          </div>
        </div>
      </div>
    </div>
  )
}
