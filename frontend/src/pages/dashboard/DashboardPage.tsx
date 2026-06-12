import { useState } from 'react'
import { Link } from 'react-router-dom'
import { useTranslation } from '@/shared/i18n'
import {
  ApprovalPulse,
  BarChart,
  Badge,
  Button,
  Card,
  Chip,
  Donut,
  Table,
  TableWrap,
  Td,
  Th,
  Tr,
  useToast,
} from '@/shared/ui'
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
    summary: '配筋検査合格後に打設開始…',
  },
  {
    id: 'r2',
    submitter: '鈴木 花子',
    date: '06-12',
    title: '現場C 設備搬入',
    summary: '空調室外機を搬入・仮置き…',
  },
  {
    id: 'r3',
    submitter: '田中 一郎',
    date: '06-12',
    title: '安全パトロール',
    summary: '3箇所で是正指示…',
  },
  {
    id: 'r4',
    submitter: '佐々木 健',
    date: '06-11',
    title: '現場B 仮設足場',
    summary: '— 要約なし',
  },
]

const DAILY: BarDatum[] = [
  { label: '月', value: 6 },
  { label: '火', value: 9 },
  { label: '水', value: 7 },
  { label: '木', value: 11 },
  { label: '金', value: 12, today: true },
  { label: '土', value: 2 },
  { label: '日', value: 1 },
]

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
    toast.show(t('report.review.approve'))
  }

  return (
    <div className="mx-auto flex w-full max-w-6xl flex-col gap-5">
      <div>
        <h2 className="text-xl font-bold text-fg">{t('common.nav.dashboard')}</h2>
        <p className="mt-1 text-sm text-fg-muted">{t('dashboard.subtitle')}</p>
      </div>

      {/* KPI row */}
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <Kpi label={t('dashboard.kpi.pending')} value={queue.length} tone="warn" />
        <Kpi label={t('dashboard.kpi.today')} value={12} />
        <Kpi label={t('dashboard.kpi.week')} value={48} />
        <Kpi label={t('dashboard.kpi.rejectRate')} value="4.1%" tone="rejected" />
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
            <TableWrap>
              <Table className="min-w-96">
                <thead>
                  <Tr>
                    <Th>{t('report.col.user')}</Th>
                    <Th className="w-16">{t('report.col.workDate')}</Th>
                    <Th>{t('report.col.title')}</Th>
                    <Th className="w-24" />
                  </Tr>
                </thead>
                <tbody>
                  {queue.map((r) => (
                    <Tr key={r.id}>
                      <Td className="text-fg">{r.submitter}</Td>
                      <Td className="text-fg-muted tnum">{r.date}</Td>
                      <Td>
                        <span className="block truncate font-medium text-fg">{r.title}</span>
                        <span className="block truncate text-xs text-fg-faint">{r.summary}</span>
                      </Td>
                      <Td>
                        <Button
                          variant="success"
                          size="sm"
                          onClick={() => {
                            approve(r.id)
                          }}
                        >
                          {t('report.review.approve')}
                        </Button>
                      </Td>
                    </Tr>
                  ))}
                </tbody>
              </Table>
            </TableWrap>
          )}
        </Card>

        <Card>
          <h3 className="mb-3 text-sm font-bold text-fg">{t('dashboard.quick.title')}</h3>
          <div className="flex flex-col gap-2">
            <Link to="/reports">
              <Chip className="w-full justify-between">
                <span>{t('dashboard.quick.pending')}</span>
                <Badge tone="warn">{queue.length}</Badge>
              </Chip>
            </Link>
            <Link to="/reports">
              <Chip className="w-full justify-between">
                <span>{t('dashboard.quick.today')}</span>
                <Badge tone="neutral">12</Badge>
              </Chip>
            </Link>
            <Link to="/reports">
              <Chip className="w-full justify-between">
                <span>{t('dashboard.quick.week')}</span>
                <Badge tone="neutral">48</Badge>
              </Chip>
            </Link>
            <Link to="/export" className="mt-1">
              <Button className="w-full">{t('dashboard.quick.toExport')}</Button>
            </Link>
          </div>
        </Card>
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
}: {
  label: string
  value: number | string
  tone?: 'warn' | 'rejected'
}) {
  const valueColor =
    tone === 'warn' ? 'text-warn' : tone === 'rejected' ? 'text-rejected' : 'text-fg'
  return (
    <Card>
      <p className="text-xs text-fg-muted">{label}</p>
      <p className={`mt-1.5 text-3xl font-extrabold tnum ${valueColor}`}>{value}</p>
    </Card>
  )
}
