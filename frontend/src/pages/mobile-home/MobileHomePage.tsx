import { useState } from 'react'
import { useSyncExternalStore } from 'react'
import { Link } from 'react-router-dom'
import { getCurrentUser, subscribeCurrentUser } from '@/entities/auth'
import { useReportListQuery, type ReportStatus } from '@/entities/report'
import { useTranslation } from '@/shared/i18n'
import type { MessageKey } from '@/shared/i18n'
import { formatCalendarDate } from '@/shared/lib/format-date'
import { Badge } from '@/shared/ui'
import { cn } from '@/shared/lib/cn'

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

function todayJa(): string {
  const d = new Date()
  const w = ['日', '月', '火', '水', '木', '金', '土'][d.getDay()]
  return `${String(d.getFullYear())}年${String(d.getMonth() + 1)}月${String(d.getDate())}日（${w}）`
}

export function MobileHomePage() {
  const { t } = useTranslation()
  const user = useSyncExternalStore(subscribeCurrentUser, getCurrentUser)
  const reportsQuery = useReportListQuery({ limit: 20, offset: 0 })
  const reports = reportsQuery.data?.items ?? []
  const [role, setRole] = useState<'submitter' | 'approver'>('submitter')

  const todays = reports.find((r) => r.workDate === todayIso())
  const recent = reports.slice(0, 5)

  return (
    <div className="flex flex-col">
      {/* gradient header */}
      <header className="bg-gradient-to-br from-accent to-x-accent-deep px-4 pb-6 pt-6 text-text-inverse">
        <div className="flex items-center justify-between">
          <span className="text-lg font-extrabold tracking-wide">{t('common.app.name')}</span>
          <div className="flex items-center gap-2">
            <Link
              to="/notifications"
              aria-label={t('shell.notifications.title')}
              className="grid h-9 w-9 place-items-center rounded-x-pill bg-white/15 text-base"
            >
              ◔
            </Link>
            <span className="grid h-9 w-9 place-items-center rounded-x-pill bg-white/15 text-sm font-bold">
              {user?.name.slice(0, 1) ?? '?'}
            </span>
          </div>
        </div>

        {/* role toggle (prototype affordance) */}
        <div className="mt-3 flex w-fit gap-0.5 rounded-x-pill bg-white/15 p-0.5 text-xs font-bold">
          <button
            type="button"
            onClick={() => {
              setRole('submitter')
            }}
            className={cn(
              'rounded-x-pill px-4 py-1.5',
              role === 'submitter' ? 'bg-surface-raised text-on-accent' : 'text-text-inverse/80',
            )}
          >
            {t('mobile.home.roleSubmitter')}
          </button>
          <button
            type="button"
            onClick={() => {
              setRole('approver')
            }}
            className={cn(
              'rounded-x-pill px-4 py-1.5',
              role === 'approver' ? 'bg-surface-raised text-on-accent' : 'text-text-inverse/80',
            )}
          >
            {t('mobile.home.roleApprover')}
          </button>
        </div>

        <p className="mt-4 text-sm text-text-inverse/80 tabular-nums">{todayJa()}</p>
        <p className="text-xl font-bold">
          {t('mobile.home.greetingName', { name: user?.name ?? '' })}
        </p>
      </header>

      <div className="flex flex-col gap-4 p-4">
        {/* today status */}
        <div className="flex items-center gap-3 rounded-x-card border border-border bg-surface-raised p-4 shadow-x-card">
          {todays === undefined ? (
            <>
              <span className="grid h-11 w-11 flex-none place-items-center rounded-x-input bg-warn-soft text-warn">
                ●
              </span>
              <div>
                <p className="font-bold text-text-primary">{t('mobile.home.notSubmittedTitle')}</p>
                <p className="text-xs text-text-muted">{t('mobile.home.notSubmittedSub')}</p>
              </div>
            </>
          ) : (
            <>
              <span className="grid h-11 w-11 flex-none place-items-center rounded-x-input bg-x-approved-soft text-x-approved">
                ✓
              </span>
              <div className="flex flex-1 items-center justify-between gap-2">
                <div>
                  <p className="text-xs text-text-muted">{t('mobile.home.todayStatus')}</p>
                  <p className="font-mono text-sm text-text-primary tabular-nums">{todayIso()}</p>
                </div>
                <Badge tone={statusTone[todays.status]}>{t(statusKey[todays.status])}</Badge>
              </div>
            </>
          )}
        </div>

        {/* big CTA */}
        <Link
          to="/reports/new"
          className="grid place-items-center rounded-x-pill bg-accent py-4 text-base font-bold text-text-inverse shadow-x-btn active:scale-95"
        >
          ＋ {t('mobile.home.cta')}
        </Link>

        {/* recent */}
        <div>
          <div className="mb-2 flex items-center justify-between">
            <h2 className="text-sm font-bold text-text-primary">{t('mobile.home.recent')}</h2>
            <Link to="/reports" className="text-xs font-semibold text-accent">
              {t('mobile.home.viewAll')} ›
            </Link>
          </div>
          <div className="flex flex-col gap-2.5">
            {recent.map((r) => (
              <Link
                key={r.id}
                to={`/reports/${r.id}`}
                className="rounded-x-card border border-border bg-surface-raised p-3.5"
              >
                <div className="flex items-center justify-between gap-2">
                  <span className="truncate font-semibold text-text-primary">{r.title}</span>
                  <Badge tone={statusTone[r.status]}>{t(statusKey[r.status])}</Badge>
                </div>
                <p className="mt-1 font-mono text-xs text-text-muted tabular-nums">
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
