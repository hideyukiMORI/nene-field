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
import { useListReports } from '../model/use-list-reports'

type StatusFilter = ReportStatus | 'all'

// Filter chips match the design (no draft chip; drafts aren't reviewed here).
const STATUS_FILTERS: StatusFilter[] = ['all', 'submitted', 'approved', 'rejected']
const PAGE_SIZE = 8

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

// Reject is comment-required; target is either the whole selection or one row.
type RejectTarget = 'bulk' | { id: string } | null

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
  const [page, setPage] = useState(0)
  const [drawerIndex, setDrawerIndex] = useState<number | null>(null)
  const [reviewMode, setReviewMode] = useState<ReviewMode>(null)
  const [rejectTarget, setRejectTarget] = useState<RejectTarget>(null)
  const [rejectComment, setRejectComment] = useState('')
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

  const totalPages = Math.max(1, Math.ceil(filtered.length / PAGE_SIZE))
  const safePage = Math.min(page, totalPages - 1)
  const pageStart = safePage * PAGE_SIZE
  const paged = filtered.slice(pageStart, pageStart + PAGE_SIZE)

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
  const resetFilters = (next: () => void): void => {
    next()
    setPage(0)
  }

  const approveOne = (id: string): void => {
    approveMutation.mutate({ reportId: id })
    firePulse()
    toast.show(t('report.review.approved'))
  }
  const bulkApprove = (): void => {
    for (const id of selected) approveMutation.mutate({ reportId: id })
    clearSelection()
    firePulse()
    toast.show(t('report.review.approved'))
  }

  const submitReject = (): void => {
    const comment = rejectComment.trim()
    if (comment === '' || rejectTarget === null) return
    const ids = rejectTarget === 'bulk' ? [...selected] : [rejectTarget.id]
    for (const id of ids) rejectMutation.mutate({ reportId: id, comment })
    setRejectTarget(null)
    setRejectComment('')
    if (rejectTarget === 'bulk') clearSelection()
    toast.show(t('report.review.rejected'))
  }

  const moveDrawer = (delta: number): void => {
    setDrawerIndex((idx) => {
      if (idx === null) return null
      const nextIdx = idx + delta
      return nextIdx >= 0 && nextIdx < filtered.length ? nextIdx : idx
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
  const rangeFrom = filtered.length === 0 ? 0 : pageStart + 1
  const rangeTo = Math.min(pageStart + PAGE_SIZE, filtered.length)

  return (
    <div className="flex h-full flex-col">
      {/* pinned toolbar (作業卓): flex-none white bar, body below scrolls */}
      <div className="relative z-10 flex flex-none flex-col gap-3.5 border-b border-border bg-surface-raised px-6.5 py-4 shadow-x-toolbar">
        {/* row: title + search + submitter + CSV */}
        <div className="flex flex-wrap items-center gap-2.5">
          <h2 className="mr-1 text-lg font-bold text-text-primary">{t('report.list.title')}</h2>
          <div className="flex w-70 items-center gap-2 rounded-x-pill border border-border bg-surface-overlay px-3.5 py-2">
            <span aria-hidden className="text-ui text-x-fg-faint-2">
              ⌕
            </span>
            <input
              value={search}
              onChange={(e) => {
                resetFilters(() => {
                  setSearch(e.target.value)
                })
              }}
              placeholder={t('report.list.search')}
              className="w-full border-0 bg-transparent text-ui text-text-primary outline-none placeholder:text-x-fg-faint-2"
            />
          </div>
          <div className="w-44">
            <Select
              value={submitter}
              onChange={(e) => {
                resetFilters(() => {
                  setSubmitter(e.target.value)
                })
              }}
              className="rounded-x-pill"
            >
              <option value="">{t('report.list.submitterAll')}</option>
              {submitters.map((name) => (
                <option key={name} value={name}>
                  {name}
                </option>
              ))}
            </Select>
          </div>
          <div className="flex-1" />
          <Link to="/export">
            <Button variant="ghost" size="sm">
              ⬇ {t('report.list.csvExport')}
            </Button>
          </Link>
        </div>

        {/* status chips + count */}
        <div className="flex flex-wrap items-center gap-2">
          {STATUS_FILTERS.map((s) => (
            <Chip
              key={s}
              active={status === s}
              onClick={() => {
                resetFilters(() => {
                  setStatus(s)
                })
              }}
            >
              {s === 'all' ? t('report.list.filter.all') : t(statusKey[s])}
            </Chip>
          ))}
          <div className="flex-1" />
          <span className="text-label text-text-faint tabular-nums">
            {t('report.list.count', { count: filtered.length })}
          </span>
        </div>
      </div>

      {/* full-width bulk action bar (accent), shown when rows are selected */}
      {selected.size > 0 && (
        <div className="flex flex-none items-center gap-3 bg-accent px-6.5 py-2.75 text-text-inverse">
          <span className="text-ui font-semibold">
            {t('report.list.bulk.selected', { count: selected.size })}
          </span>
          <div className="flex-1" />
          <button
            type="button"
            onClick={bulkApprove}
            className="rounded-x-pill bg-surface-raised px-4.5 py-2 text-ui font-bold text-on-accent"
          >
            {t('report.list.bulk.approve')}
          </button>
          <button
            type="button"
            onClick={() => {
              setRejectTarget('bulk')
            }}
            className="rounded-x-pill border border-text-inverse/55 px-4.5 py-2 text-ui font-bold text-text-inverse active:bg-text-inverse/10"
          >
            {t('report.list.bulk.reject')}
          </button>
          <button
            type="button"
            onClick={clearSelection}
            className="px-1.5 py-2 text-ui font-semibold text-text-inverse/80"
          >
            {t('report.list.bulk.clear')}
          </button>
        </div>
      )}

      {/* body — the only scrolling region under the pinned toolbar */}
      <div className="min-h-0 flex-1 overflow-y-auto px-6.5 pt-1.5 pb-6">
        {filtered.length === 0 ? (
          <EmptyState message={t('report.list.emptyFiltered')} />
        ) : (
          <TableWrap>
            <Table className="min-w-180">
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
                  <Th className="w-36">{t('report.col.user')}</Th>
                  <Th className="w-24">{t('report.col.workDate')}</Th>
                  <Th>{t('report.col.titleAi')}</Th>
                  <Th className="w-24">{t('report.col.status')}</Th>
                  <Th className="w-36 text-right">{t('report.list.colActions')}</Th>
                </Tr>
              </thead>
              <tbody>
                {paged.map((r, i) => (
                  <Tr
                    key={r.id}
                    interactive
                    selected={selected.has(r.id)}
                    onClick={() => {
                      setDrawerIndex(pageStart + i)
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
                    <Td>
                      <div className="flex items-center gap-2.5">
                        <span className="grid h-7.5 w-7.5 flex-none place-items-center rounded-x-pill bg-accent-soft text-caption font-bold text-on-accent">
                          {r.userName.slice(0, 1)}
                        </span>
                        <span className="whitespace-nowrap font-semibold text-text-primary">
                          {r.userName}
                        </span>
                      </div>
                    </Td>
                    <Td className="whitespace-nowrap text-text-muted tabular-nums">
                      {formatCalendarDate(r.workDate)}
                    </Td>
                    <Td>
                      <span className="block truncate font-semibold text-text-primary">{r.title}</span>
                      <span className="block truncate text-caption text-text-faint">
                        {r.aiSummary ?? t('report.list.aiSummaryNone')}
                      </span>
                    </Td>
                    <Td>
                      <Badge tone={statusTone[r.status]}>{t(statusKey[r.status])}</Badge>
                    </Td>
                    <Td
                      onClick={(e) => {
                        e.stopPropagation()
                      }}
                    >
                      {r.status === 'submitted' ? (
                        <div className="flex justify-end gap-1.5">
                          <Button
                            variant="danger-ghost"
                            size="sm"
                            onClick={() => {
                              setRejectTarget({ id: r.id })
                            }}
                          >
                            {t('report.review.reject')}
                          </Button>
                          <Button
                            variant="success"
                            size="sm"
                            onClick={() => {
                              approveOne(r.id)
                            }}
                          >
                            {t('report.review.approve')}
                          </Button>
                        </div>
                      ) : (
                        <span className="block text-right text-label text-x-fg-faint-2">
                          {t('report.list.processed')}
                        </span>
                      )}
                    </Td>
                  </Tr>
                ))}
              </tbody>
            </Table>
          </TableWrap>
        )}
      </div>

      {/* pagination footer */}
      {filtered.length > 0 && (
        <div className="flex flex-none items-center gap-3 border-t border-border bg-surface-raised px-6.5 py-3">
          <span className="text-label text-x-fg-muted-2 tabular-nums">
            {t('report.list.pageInfo', { from: rangeFrom, to: rangeTo, total: filtered.length })}
          </span>
          <div className="flex-1" />
          <button
            type="button"
            aria-label={t('common.actions.previous')}
            disabled={safePage === 0}
            onClick={() => {
              setPage((p) => Math.max(0, p - 1))
            }}
            className="grid h-8 w-8 place-items-center rounded-x-pill border border-border-strong text-text-muted disabled:opacity-40 hover:bg-surface-overlay"
          >
            ‹
          </button>
          <span className="min-w-13 text-center text-ui font-semibold text-text-muted tabular-nums">
            {safePage + 1} / {totalPages}
          </span>
          <button
            type="button"
            aria-label={t('common.actions.next')}
            disabled={safePage >= totalPages - 1}
            onClick={() => {
              setPage((p) => Math.min(totalPages - 1, p + 1))
            }}
            className="grid h-8 w-8 place-items-center rounded-x-pill border border-border-strong text-text-muted disabled:opacity-40 hover:bg-surface-overlay"
          >
            ›
          </button>
        </div>
      )}

      {/* continuous-review drawer */}
      <Drawer
        open={current !== undefined}
        closeLabel={t('common.actions.close')}
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
              className="grid h-8 w-8 place-items-center rounded-x-pill text-text-muted hover:bg-surface-overlay"
            >
              ‹
            </button>
            <button
              type="button"
              aria-label={t('common.actions.next')}
              onClick={() => {
                moveDrawer(1)
              }}
              className="grid h-8 w-8 place-items-center rounded-x-pill text-text-muted hover:bg-surface-overlay"
            >
              ›
            </button>
            <span className="ml-1 text-caption text-text-faint tabular-nums">
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
            <span className="text-sm text-text-faint">{t('report.drawer.processed')}</span>
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

      {/* reject modal (row or bulk) — comment required */}
      <Modal
        open={rejectTarget !== null}
        closeLabel={t('common.actions.close')}
        onClose={() => {
          setRejectTarget(null)
          setRejectComment('')
        }}
        title={t('report.review.rejectTitle')}
        footer={
          <>
            <Button
              variant="ghost"
              onClick={() => {
                setRejectTarget(null)
                setRejectComment('')
              }}
            >
              {t('common.actions.cancel')}
            </Button>
            <Button variant="danger" onClick={submitReject} disabled={rejectComment.trim() === ''}>
              {t('report.review.reject')}
            </Button>
          </>
        }
      >
        <Textarea
          value={rejectComment}
          onChange={(e) => {
            setRejectComment(e.target.value)
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
        <h3 className="text-lg font-bold text-text-primary">{report.title}</h3>
        <Badge tone={statusTone[report.status]}>{t(statusKey[report.status])}</Badge>
      </div>
      <dl className="flex flex-col gap-2 text-sm">
        <div className="flex gap-3">
          <dt className="w-24 flex-none text-text-muted">{t('report.col.user')}</dt>
          <dd className="text-text-primary">{report.userName}</dd>
        </div>
        <div className="flex gap-3">
          <dt className="w-24 flex-none text-text-muted">{t('report.col.workDate')}</dt>
          <dd className="text-text-primary tnum">{formatCalendarDate(report.workDate)}</dd>
        </div>
        {report.projectCode !== null && (
          <div className="flex gap-3">
            <dt className="w-24 flex-none text-text-muted">{t('report.field.projectCode')}</dt>
            <dd className="text-text-primary">{report.projectCode}</dd>
          </div>
        )}
      </dl>
      {report.aiSummary !== null && (
        <div className="rounded-x-input bg-x-ai-soft px-4 py-3 text-sm text-text-primary">
          <span className="text-xs font-semibold text-x-ai">{t('report.col.aiSummary')}</span>
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
