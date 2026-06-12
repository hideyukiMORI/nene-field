import { useState } from 'react'
import { Link } from 'react-router-dom'
import { useTranslation } from '@/shared/i18n'
import { ApprovalPulse, BarChart, Button, Card, Donut, useToast } from '@/shared/ui'
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
  { label: '月', value: 7 },
  { label: '火', value: 9 },
  { label: '水', value: 6 },
  { label: '木', value: 11 },
  { label: '金', value: 8 },
  { label: '土', value: 10 },
  { label: '日', value: 4, today: true },
]

const AVATAR_BG = ['bg-submitted-soft', 'bg-approved-soft', 'bg-warn-soft', 'bg-ai-soft']
const AVATAR_FG = ['text-submitted', 'text-approved', 'text-warn', 'text-ai']

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
    { tone: 'approved', value: 41, label: t('report.status.approved') },
    { tone: 'submitted', value: queue.length, label: t('report.status.submitted') },
    { tone: 'rejected', value: 2, label: t('report.status.rejected') },
    { tone: 'draft', value: 5, label: t('report.status.draft') },
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
    <div className="mx-auto flex w-full max-w-6xl flex-col gap-5">
      {/* page header */}
      <div className="flex flex-wrap items-end justify-between gap-3">
        <div>
          <h2 className="text-xl font-bold text-fg">{t('common.nav.dashboard')}</h2>
          <p className="mt-1 text-sm text-fg-muted">
            {t('dashboard.view')} · <span className="tabular-nums">{todayLabel()}</span>
          </p>
        </div>
        <Link to="/reports">
          <Button>{t('dashboard.reviewCta')} →</Button>
        </Link>
      </div>

      {/* KPI row */}
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <Kpi label={t('dashboard.kpi.pending')} value={queue.length} tone="warn" delta="要対応" />
        <Kpi label={t('dashboard.kpi.today')} value={12} delta="前日比 +3" deltaUp />
        <Kpi label={t('dashboard.kpi.week')} value={48} delta="うち承認 41" />
        <Kpi label={t('dashboard.kpi.rejectRate')} value="4.1%" tone="rejected" delta="2 / 48 件" />
      </div>

      {/* charts */}
      <div className="grid gap-4 lg:grid-cols-2">
        <Card>
          <h3 className="mb-4 text-sm font-bold text-fg">{t('dashboard.chart.daily')}</h3>
          <BarChart data={DAILY} />
        </Card>
        <Card>
          <h3 className="mb-4 text-sm font-bold text-fg">{t('dashboard.chart.status')}</h3>
          <Donut segments={statusSegments} />
        </Card>
      </div>

      {/* queue + quick filters */}
      <div className="grid gap-4 lg:grid-cols-3">
        <Card padded={false} className="lg:col-span-2">
          <div className="flex items-center justify-between border-b border-border px-5 py-3.5">
            <h3 className="text-sm font-bold text-fg">{t('dashboard.queue.title')}</h3>
            <Link to="/reports">
              <Button variant="ghost" size="sm">
                {t('dashboard.queue.viewAll')}
              </Button>
            </Link>
          </div>
          {queue.length === 0 ? (
            <p className="px-5 py-10 text-center text-sm text-fg-faint">
              {t('dashboard.queue.empty')}
            </p>
          ) : (
            <ul>
              {queue.map((r, i) => (
                <li
                  key={r.id}
                  className="flex items-center gap-3 border-b border-border-2 px-5 py-3 last:border-b-0"
                >
                  <span
                    className={`grid h-9 w-9 flex-none place-items-center rounded-pill text-sm font-bold ${AVATAR_BG[i % 4]} ${AVATAR_FG[i % 4]}`}
                  >
                    {r.submitter.slice(0, 1)}
                  </span>
                  <div className="min-w-0 flex-1">
                    <div className="flex items-center gap-2">
                      <span className="truncate font-semibold text-fg">{r.title}</span>
                      <span className="flex-none text-xs text-fg-faint tabular-nums">{r.date}</span>
                    </div>
                    <p className="truncate text-xs text-fg-faint">{r.summary}</p>
                  </div>
                  <div className="flex flex-none gap-2">
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
                  </div>
                </li>
              ))}
            </ul>
          )}
        </Card>

        <div className="flex flex-col gap-4">
          <Card padded={false}>
            <h3 className="border-b border-border px-5 py-3.5 text-sm font-bold text-fg">
              {t('dashboard.quick.title')}
            </h3>
            <ul className="p-2">
              <QuickItem icon="⏳" label={t('dashboard.quick.pending')} count={queue.length} />
              <QuickItem icon="📅" label={t('dashboard.quick.today')} count={12} />
              <QuickItem icon="🗓" label={t('dashboard.quick.week')} count={48} />
            </ul>
            <div className="px-3 pb-3">
              <Link to="/export">
                <Button className="w-full">⬇ {t('dashboard.quick.toExport')}</Button>
              </Link>
            </div>
          </Card>

          <Card>
            <h3 className="mb-2 text-sm font-bold text-fg">{t('dashboard.summary.title')}</h3>
            <p className="text-sm leading-relaxed text-fg-muted">
              {t('dashboard.summary.body', { total: 48, approved: 41, pending: queue.length })}
            </p>
          </Card>
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
  delta,
  deltaUp = false,
}: {
  label: string
  value: number | string
  tone?: 'warn' | 'rejected'
  delta?: string
  deltaUp?: boolean
}) {
  const valueColor =
    tone === 'warn' ? 'text-warn' : tone === 'rejected' ? 'text-rejected' : 'text-fg'
  return (
    <Card>
      <p className="text-xs text-fg-muted">{label}</p>
      <p className={`mt-1 text-3xl font-extrabold tabular-nums ${valueColor}`}>{value}</p>
      {delta !== undefined && (
        <p className={`mt-1 text-xs ${deltaUp ? 'text-approved' : 'text-fg-faint'}`}>{delta}</p>
      )}
    </Card>
  )
}

function QuickItem({ icon, label, count }: { icon: string; label: string; count: number }) {
  return (
    <li>
      <Link
        to="/reports"
        className="flex items-center gap-2.5 rounded-input px-3 py-2.5 hover:bg-surface-overlay"
      >
        <span className="text-base">{icon}</span>
        <span className="flex-1 text-sm text-fg">{label}</span>
        <span className="text-sm font-bold text-fg tabular-nums">{count}</span>
      </Link>
    </li>
  )
}
