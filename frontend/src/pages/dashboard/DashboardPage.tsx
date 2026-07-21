import { useState, type ReactNode } from 'react'
import { Link } from 'react-router-dom'
import { useTranslation } from '@/shared/i18n'
import { cn } from '@/shared/lib/cn'
import { ApprovalPulse, BarChart, Button, Donut, useToast } from '@/shared/ui'
import type { BarDatum, DonutSegment } from '@/shared/ui'

interface QueueRow {
  id: string
  submitter: string
  date: string
  title: string
  summary: string
}

// Representative demo data (dummy per design handoff §0; wire to the reports API later).
const INITIAL_QUEUE: QueueRow[] = [
  {
    id: 'r1',
    submitter: '山田 太郎',
    date: '06-12',
    title: '現場A 基礎打設',
    summary: '配筋検査合格後に打設開始、午後より養生。',
  },
  {
    id: 'r2',
    submitter: '鈴木 花子',
    date: '06-12',
    title: '現場C 設備搬入',
    summary: '空調室外機を搬入・仮置き。配管接続まで完了。',
  },
  {
    id: 'r3',
    submitter: '田中 一郎',
    date: '06-12',
    title: '安全パトロール',
    summary: '3箇所で是正指示。',
  },
  {
    id: 'r4',
    submitter: '佐々木 健',
    date: '06-11',
    title: '現場B 仮設足場',
    summary: '足場の解体、廃材を搬出。',
  },
]

const DAILY: BarDatum[] = [
  { label: '6/6', value: 7 },
  { label: '6/7', value: 9 },
  { label: '6/8', value: 6 },
  { label: '6/9', value: 11 },
  { label: '6/10', value: 8 },
  { label: '6/11', value: 10 },
  { label: '6/12', value: 4, today: true },
]

const KPI_TODAY = 12
const KPI_WEEK = 48
const KPI_APPROVED = 41
const KPI_REJECTED = 2

function todayLabel(): string {
  const d = new Date()
  const w = ['日', '月', '火', '水', '木', '金', '土'][d.getDay()]
  return `${d.toISOString().slice(0, 10)}（${w}）`
}

export function DashboardPage() {
  const { t } = useTranslation()
  const toast = useToast()
  const [queue, setQueue] = useState<QueueRow[]>(INITIAL_QUEUE)
  const [pulse, setPulse] = useState(0)

  const statusSegments: DonutSegment[] = [
    { tone: 'approved', value: KPI_APPROVED, label: t('report.status.approved') },
    { tone: 'submitted', value: queue.length, label: t('report.status.submitted') },
    { tone: 'rejected', value: KPI_REJECTED, label: t('report.status.rejected') },
  ]

  const approve = (id: string): void => {
    setQueue((rows) => rows.filter((r) => r.id !== id))
    setPulse((n) => n + 1)
    toast.show(t('report.review.approved'))
  }
  const reject = (id: string): void => {
    setQueue((rows) => rows.filter((r) => r.id !== id))
    toast.show(t('report.review.rejected'))
  }

  return (
    // Plain workspace screen: this page owns the 26/30/40 content padding.
    <div className="flex flex-col gap-5.5 px-7.5 pt-6.5 pb-10">
      {/* ── header ───────────────────────────────────────────────── */}
      <div className="flex flex-wrap items-end gap-3.5">
        <div className="min-w-0">
          <h2 className="text-screen-title font-bold text-text-primary">
            {t('common.nav.dashboard')}
          </h2>
          <p className="mt-0.5 text-ui text-x-fg-muted-2">
            {t('dashboard.view')} ・ <span className="tabular-nums">{todayLabel()}</span>
          </p>
        </div>
        <div className="flex-1" />
        <Link to="/reports">
          <Button>{t('dashboard.reviewCta')} →</Button>
        </Link>
      </div>

      {/* ── KPI row (4) ──────────────────────────────────────────── */}
      <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <Kpi
          label={t('dashboard.kpi.pending')}
          value={queue.length}
          tone="accent"
          sub={t('dashboard.kpi.pendingSub')}
        />
        <Kpi
          label={t('dashboard.kpi.today')}
          value={KPI_TODAY}
          sub={t('dashboard.kpi.todaySub')}
          subTone="approved"
        />
        <Kpi
          label={t('dashboard.kpi.week')}
          value={KPI_WEEK}
          sub={t('dashboard.kpi.weekSub', { count: KPI_APPROVED })}
        />
        <Kpi
          label={t('dashboard.kpi.reject')}
          value={KPI_REJECTED}
          tone="rejected"
          sub={`${t('dashboard.kpi.rejectRate')} 4.1%`}
        />
      </div>

      {/* ── charts: trend (≈1.55fr) + status (1fr) ───────────────── */}
      <div className="grid gap-4.5 lg:grid-cols-5">
        <div className="rounded-2xl border border-border bg-surface-raised px-5 py-4.5 lg:col-span-3">
          <h3 className="text-sm font-bold text-text-primary">{t('dashboard.chart.daily')}</h3>
          <p className="mb-4.5 text-caption text-x-fg-faint-2">{t('dashboard.chart.dailySub')}</p>
          <BarChart data={DAILY} />
        </div>
        <div className="rounded-2xl border border-border bg-surface-raised px-5 py-4.5 lg:col-span-2">
          <h3 className="mb-4.5 text-sm font-bold text-text-primary">
            {t('dashboard.chart.status')}
          </h3>
          <Donut segments={statusSegments} />
        </div>
      </div>

      {/* ── pending queue + side column (320px) ──────────────────── */}
      <div className="flex flex-col gap-4.5 lg:flex-row">
        <div className="min-w-0 flex-1 overflow-hidden rounded-2xl border border-border bg-surface-raised">
          <div className="flex items-center gap-2 border-b border-border px-4.5 py-3.5">
            <h3 className="flex-1 text-sm font-bold text-text-primary">
              {t('dashboard.queue.title')}
            </h3>
            <Link
              to="/reports"
              className="text-label font-semibold text-on-accent hover:text-accent"
            >
              {t('dashboard.queue.viewAll')} ›
            </Link>
          </div>
          {queue.length === 0 ? (
            <div className="px-5 py-11 text-center">
              <div className="mb-2 text-3xl text-x-btn-success">✓</div>
              <p className="text-ui font-semibold text-x-fg-muted-2">
                {t('dashboard.queue.empty')}
              </p>
            </div>
          ) : (
            <ul>
              {queue.map((r) => (
                <li
                  key={r.id}
                  className="flex items-center gap-3.5 border-b border-border px-4.5 py-3 last:border-b-0"
                >
                  <span className="grid h-8.5 w-8.5 flex-none place-items-center rounded-x-pill bg-accent-soft text-label font-bold text-on-accent">
                    {r.submitter.slice(0, 1)}
                  </span>
                  <div className="min-w-0 flex-1">
                    <div className="flex items-center gap-2">
                      <span className="whitespace-nowrap text-ui font-semibold text-text-primary">
                        {r.submitter}
                      </span>
                      <span className="text-caption text-x-fg-faint-2 tabular-nums">{r.date}</span>
                    </div>
                    <p className="truncate text-label text-x-fg-muted-2">
                      {r.title} ・ {r.summary}
                    </p>
                  </div>
                  <Button
                    variant="danger-ghost"
                    size="sm"
                    onClick={() => {
                      reject(r.id)
                    }}
                  >
                    {t('report.review.reject')}
                  </Button>
                  <Button
                    variant="success"
                    size="sm"
                    onClick={() => {
                      approve(r.id)
                    }}
                  >
                    {t('report.review.approve')}
                  </Button>
                </li>
              ))}
            </ul>
          )}
        </div>

        <div className="flex flex-col gap-4.5 lg:w-80 lg:flex-none">
          {/* quick filters */}
          <div className="rounded-2xl border border-border bg-surface-raised px-4.5 py-4">
            <h3 className="mb-3 text-ui font-bold text-text-primary">
              {t('dashboard.quick.title')}
            </h3>
            <QuickRow icon="⏳" to="/reports" label={t('dashboard.quick.pending')}>
              <span className="text-label font-bold text-on-accent tabular-nums">
                {queue.length}
              </span>
            </QuickRow>
            <QuickRow icon="▤" to="/reports" label={t('dashboard.quick.today')}>
              <span className="text-label font-bold text-x-fg-muted-2 tabular-nums">
                {KPI_TODAY}
              </span>
            </QuickRow>
            <QuickRow icon="⬇" to="/export" label={t('dashboard.quick.toExport')}>
              <span className="text-base text-x-fg-faint-2">›</span>
            </QuickRow>
          </div>

          {/* weekly summary (dark brand card) */}
          <div className="rounded-2xl bg-gradient-to-b from-x-accent-deep to-x-accent-deep-2 px-4.5 py-4.5 text-text-inverse">
            <h3 className="mb-1.5 text-ui font-bold">{t('dashboard.summary.title')}</h3>
            <p className="text-xs leading-relaxed text-accent-soft">
              {t('dashboard.summary.body', {
                total: KPI_WEEK,
                approved: KPI_APPROVED,
                pending: queue.length,
              })}
            </p>
          </div>
        </div>
      </div>

      <ApprovalPulse
        trigger={pulse}
        onDone={() => {
          setPulse(0)
        }}
      />
    </div>
  )
}

function Kpi({
  label,
  value,
  tone,
  sub,
  subTone,
}: {
  label: string
  value: number | string
  tone?: 'accent' | 'rejected'
  sub?: string
  subTone?: 'approved'
}) {
  const valueColor =
    tone === 'accent'
      ? 'text-on-accent'
      : tone === 'rejected'
        ? 'text-x-rejected'
        : 'text-text-primary'
  const subColor = subTone === 'approved' ? 'text-x-btn-success' : 'text-x-fg-faint-2'
  return (
    <div className="rounded-2xl border border-border bg-surface-raised px-5 py-4.5">
      <p className="text-label text-x-fg-muted-2">{label}</p>
      <p className={cn('mt-1 text-kpi font-bold tabular-nums', valueColor)}>{value}</p>
      {sub !== undefined && <p className={cn('mt-0.5 text-caption', subColor)}>{sub}</p>}
    </div>
  )
}

function QuickRow({
  icon,
  to,
  label,
  children,
}: {
  icon: string
  to: string
  label: string
  children: ReactNode
}) {
  return (
    <Link
      to={to}
      className="mb-2 flex items-center gap-2.5 rounded-xl bg-surface-overlay px-3 py-2.75 last:mb-0 hover:bg-surface-overlay"
    >
      <span aria-hidden className="text-base">
        {icon}
      </span>
      <span className="flex-1 text-ui font-semibold text-text-primary">{label}</span>
      {children}
    </Link>
  )
}
