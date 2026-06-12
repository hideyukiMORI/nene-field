import { useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import {
  useApproveReportMutation,
  useRejectReportMutation,
  type ReportStatus,
  type ReportSummary,
} from '@/entities/report'
import { ReviewModals, type ReviewMode } from '@/features/review-report'
import { useTranslation } from '@/shared/i18n'
import type { MessageKey } from '@/shared/i18n'
import { formatCalendarDate } from '@/shared/lib/format-date'
import {
  ApprovalPulse,
  Badge,
  Button,
  Checkbox,
  Chip,
  Drawer,
  EmptyState,
  ErrorState,
  Input,
  LoadingState,
  Modal,
  Select,
  Table,
  TableWrap,
  Td,
  Textarea,
  Th,
  Tr,
  useToast,
} from '@/shared/ui'
import { useListReports } from '../hooks/use-list-reports'

type StatusFilter = ReportStatus | 'all'

const STATUS_FILTERS: StatusFilter[] = ['all', 'submitted', 'approved', 'rejected', 'draft']

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

export function ListReports() {
  const { t } = useTranslation()
  const toast = useToast()
  const { reports, isLoading, isError, refetch } = useListReports()
  const approveMutation = useApproveReportMutation()
  const rejectMutation = useRejectReportMutation()

  const [search, setSearch] = useState('')
  const [submitter, setSubmitter] = useState('')
  const [status, setStatus] = useState<StatusFilter>('all')
  const [selected, setSelected] = useState<ReadonlySet<string>>(new Set())
  const [drawerIndex, setDrawerIndex] = useState<number | null>(null)
  const [reviewMode, setReviewMode] = useState<ReviewMode>(null)
  const [bulkReject, setBulkReject] = useState(false)
  const [bulkComment, setBulkComment] = useState('')
  const [pulse, setPulse] = useState(0)

  const submitters = useMemo(
    () => [...new Set(reports.map((r) => r.userName))].sort((a, b) => a.localeCompare(b)),
    [reports],
  )

  const filtered = useMemo(() => {
    const q = search.trim().toLowerCase()
    return reports.filter((r) => {
      if (status !== 'all' && r.status !== status) return false
      if (submitter !== '' && r.userName !== submitter) return false
      if (q !== '' && !`${r.title} ${r.userName}`.toLowerCase().includes(q)) return false
      return true
    })
  }, [reports, search, submitter, status])

  const selectableIds = useMemo(
    () => filtered.filter((r) => r.status === 'submitted').map((r) => r.id),
    [filtered],
  )
  const allSelected = selectableIds.length > 0 && selectableIds.every((id) => selected.has(id))
  const someSelected = selectableIds.some((id) => selected.has(id))

  const toggleOne = (id: string): void => {
    setSelected((prev) => {
      const next = new Set(prev)
      if (next.has(id)) next.delete(id)
      else next.add(id)
      return next
    })
  }
  const toggleAll = (): void => {
    setSelected(allSelected ? new Set() : new Set(selectableIds))
  }
  const clearSelection = (): void => {
    setSelected(new Set())
  }
  const firePulse = (): void => {
    setPulse((n) => n + 1)
  }

  const bulkApprove = (): void => {
    for (const id of selected) approveMutation.mutate({ reportId: id })
    clearSelection()
    firePulse()
    toast.show(t('report.review.approved'))
  }

  const submitBulkReject = (): void => {
    const comment = bulkComment.trim()
    if (comment === '') return
    for (const id of selected) rejectMutation.mutate({ reportId: id, comment })
    setBulkReject(false)
    setBulkComment('')
    clearSelection()
    toast.show(t('report.review.rejected'))
  }

  const moveDrawer = (delta: number): void => {
    setDrawerIndex((idx) => {
      if (idx === null) return null
      const next = idx + delta
      return next >= 0 && next < filtered.length ? next : idx
    })
  }
  const onReviewed = (mode: 'approve' | 'reject'): void => {
    setReviewMode(null)
    if (mode === 'approve') firePulse()
    toast.show(t(mode === 'approve' ? 'report.review.approved' : 'report.review.rejected'))
    setDrawerIndex((idx) => (idx !== null && idx + 1 < filtered.length ? idx + 1 : idx))
  }

  if (isLoading) return <LoadingState label={t('common.state.loading')} />
  if (isError) {
    return (
      <ErrorState
        message={t('report.list.error')}
        retryLabel={t('common.actions.retry')}
        onRetry={refetch}
      />
    )
  }

  const current = drawerIndex !== null ? filtered[drawerIndex] : undefined

  return (
    <div className="flex flex-col gap-4">
      {/* header row: title + search + submitter + CSV */}
      <div className="flex flex-wrap items-center gap-3">
        <h2 className="text-xl font-bold text-fg">{t('report.list.title')}</h2>
        <div className="min-w-3xs max-w-xs flex-1">
          <Input
            value={search}
            onChange={(e) => {
              setSearch(e.target.value)
            }}
            placeholder={t('report.list.search')}
          />
        </div>
        <div className="w-44">
          <Select
            value={submitter}
            onChange={(e) => {
              setSubmitter(e.target.value)
            }}
          >
            <option value="">{t('report.list.submitterAll')}</option>
            {submitters.map((name) => (
              <option key={name} value={name}>
                {name}
              </option>
            ))}
          </Select>
        </div>
        <Link to="/export" className="ml-auto">
          <Button variant="ghost">⬇ {t('report.list.csvExport')}</Button>
        </Link>
      </div>

      {/* status chips + count */}
      <div className="flex flex-wrap items-center gap-1.5">
        {STATUS_FILTERS.map((s) => (
          <Chip
            key={s}
            active={status === s}
            onClick={() => {
              setStatus(s)
            }}
          >
            {s === 'all' ? t('report.list.filter.all') : t(statusKey[s])}
          </Chip>
        ))}
        <span className="ml-auto text-sm text-fg-muted tabular-nums">
          {t('report.list.count', { count: filtered.length })}
        </span>
      </div>

      {filtered.length === 0 ? (
        <EmptyState message={t('report.list.empty')} />
      ) : (
        <div className="overflow-hidden rounded-card border border-border bg-surface-raised">
          <TableWrap>
            <Table className="min-w-160">
              <thead>
                <Tr>
                  <Th className="w-10">
                    <Checkbox
                      checked={allSelected}
                      indeterminate={!allSelected && someSelected}
                      onChange={toggleAll}
                      label="select all"
                    />
                  </Th>
                  <Th className="w-28">{t('report.col.user')}</Th>
                  <Th className="w-24">{t('report.col.workDate')}</Th>
                  <Th>{t('report.col.title')}</Th>
                  <Th className="w-24">{t('report.col.status')}</Th>
                </Tr>
              </thead>
              <tbody>
                {filtered.map((r, index) => (
                  <Tr
                    key={r.id}
                    interactive
                    selected={selected.has(r.id)}
                    onClick={() => {
                      setDrawerIndex(index)
                    }}
                  >
                    <Td
                      onClick={(e) => {
                        e.stopPropagation()
                      }}
                    >
                      {r.status === 'submitted' && (
                        <Checkbox
                          checked={selected.has(r.id)}
                          onChange={() => {
                            toggleOne(r.id)
                          }}
                          label={r.title}
                        />
                      )}
                    </Td>
                    <Td className="text-fg">{r.userName}</Td>
                    <Td className="text-fg-muted tnum">{formatCalendarDate(r.workDate)}</Td>
                    <Td>
                      <span className="block truncate font-medium text-fg">{r.title}</span>
                      <span className="block truncate text-xs text-fg-faint">
                        {r.aiSummary ?? t('report.list.aiSummaryNone')}
                      </span>
                    </Td>
                    <Td>
                      <Badge tone={statusTone[r.status]}>{t(statusKey[r.status])}</Badge>
                    </Td>
                  </Tr>
                ))}
              </tbody>
            </Table>
          </TableWrap>
        </div>
      )}

      {/* bulk action bar */}
      {selected.size > 0 && (
        <div className="sticky bottom-4 z-10 flex items-center gap-2 rounded-pill border border-border bg-surface-raised px-4 py-2.5 shadow-card">
          <span className="text-sm font-semibold text-fg">
            {t('report.list.bulk.selected', { count: selected.size })}
          </span>
          <div className="ml-auto flex gap-2">
            <Button variant="ghost" size="sm" onClick={clearSelection}>
              {t('report.list.bulk.clear')}
            </Button>
            <Button
              variant="danger-ghost"
              size="sm"
              onClick={() => {
                setBulkReject(true)
              }}
            >
              {t('report.list.bulk.reject')}
            </Button>
            <Button variant="success" size="sm" onClick={bulkApprove}>
              {t('report.list.bulk.approve')}
            </Button>
          </div>
        </div>
      )}

      {/* continuous-review drawer */}
      <Drawer
        open={current !== undefined}
        onClose={() => {
          setDrawerIndex(null)
        }}
        header={
          <div className="flex w-full items-center gap-2">
            <button
              type="button"
              aria-label={t('common.actions.previous')}
              onClick={() => {
                moveDrawer(-1)
              }}
              className="grid h-8 w-8 place-items-center rounded-pill text-fg-muted hover:bg-surface-overlay"
            >
              ‹
            </button>
            <button
              type="button"
              aria-label={t('common.actions.next')}
              onClick={() => {
                moveDrawer(1)
              }}
              className="grid h-8 w-8 place-items-center rounded-pill text-fg-muted hover:bg-surface-overlay"
            >
              ›
            </button>
            <span className="ml-1 text-xs text-fg-faint tnum">
              {t('report.drawer.position', {
                current: (drawerIndex ?? 0) + 1,
                total: filtered.length,
              })}
            </span>
          </div>
        }
        footer={
          current?.status === 'submitted' ? (
            <>
              <Button
                variant="danger-ghost"
                className="flex-1"
                onClick={() => {
                  setReviewMode('reject')
                }}
              >
                {t('report.review.reject')}
              </Button>
              <Button
                variant="success"
                className="flex-1"
                onClick={() => {
                  setReviewMode('approve')
                }}
              >
                {t('report.review.approve')}
              </Button>
            </>
          ) : (
            <span className="text-sm text-fg-faint">{t('report.drawer.processed')}</span>
          )
        }
      >
        {current !== undefined && <DrawerBody report={current} />}
      </Drawer>

      {/* single review modals (within drawer) */}
      {current !== undefined && (
        <ReviewModals
          reportId={current.id}
          context={current.aiSummary}
          mode={reviewMode}
          onClose={() => {
            setReviewMode(null)
          }}
          onReviewed={onReviewed}
        />
      )}

      {/* bulk reject modal */}
      <Modal
        open={bulkReject}
        onClose={() => {
          setBulkReject(false)
        }}
        title={t('report.review.rejectTitle')}
        footer={
          <>
            <Button
              variant="ghost"
              onClick={() => {
                setBulkReject(false)
              }}
            >
              {t('common.actions.cancel')}
            </Button>
            <Button
              variant="danger"
              onClick={submitBulkReject}
              disabled={bulkComment.trim() === ''}
            >
              {t('report.list.bulk.reject')}
            </Button>
          </>
        }
      >
        <Textarea
          value={bulkComment}
          onChange={(e) => {
            setBulkComment(e.target.value)
          }}
          placeholder={t('report.review.commentPlaceholder')}
        />
      </Modal>

      <ApprovalPulse
        trigger={pulse}
        onDone={() => {
          setPulse(0)
        }}
      />
    </div>
  )
}

function DrawerBody({ report }: { report: ReportSummary }) {
  const { t } = useTranslation()
  return (
    <div className="flex flex-col gap-4">
      <div className="flex items-start justify-between gap-3">
        <h3 className="text-lg font-bold text-fg">{report.title}</h3>
        <Badge tone={statusTone[report.status]}>{t(statusKey[report.status])}</Badge>
      </div>
      <dl className="flex flex-col gap-2 text-sm">
        <div className="flex gap-3">
          <dt className="w-24 flex-none text-fg-muted">{t('report.col.user')}</dt>
          <dd className="text-fg">{report.userName}</dd>
        </div>
        <div className="flex gap-3">
          <dt className="w-24 flex-none text-fg-muted">{t('report.col.workDate')}</dt>
          <dd className="text-fg tnum">{formatCalendarDate(report.workDate)}</dd>
        </div>
        {report.projectCode !== null && (
          <div className="flex gap-3">
            <dt className="w-24 flex-none text-fg-muted">{t('report.field.projectCode')}</dt>
            <dd className="text-fg">{report.projectCode}</dd>
          </div>
        )}
      </dl>
      {report.aiSummary !== null && (
        <div className="rounded-input bg-ai-soft px-4 py-3 text-sm text-fg">
          <span className="text-xs font-semibold text-ai">{t('report.col.aiSummary')}</span>
          <p className="mt-1">{report.aiSummary}</p>
        </div>
      )}
      {report.tags.length > 0 && (
        <div className="flex flex-wrap gap-1.5">
          {report.tags.map((tag) => (
            <Chip key={tag}>{tag}</Chip>
          ))}
        </div>
      )}
    </div>
  )
}
