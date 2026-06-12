import { useState } from 'react'
import type { AuditEvent } from '@/entities/audit-event'
import { useTranslation } from '@/shared/i18n'
import type { MessageKey } from '@/shared/i18n'
import { formatJstDateTime } from '@/shared/lib/format-date'
import {
  Badge,
  Button,
  Chip,
  EmptyState,
  ErrorState,
  InlineAlert,
  Input,
  LoadingState,
  Modal,
  Stack,
  Table,
  TableWrap,
  Td,
  Text,
  Th,
  Tr,
} from '@/shared/ui'
import { useAuditLog, type AuditFilterValues } from '../hooks/use-audit-log'
import { useExportAudit } from '../hooks/use-export-audit'

type BadgeTone = 'approved' | 'rejected' | 'submitted' | 'info' | 'neutral'

function eventTone(eventName: string): BadgeTone {
  if (eventName.includes('approved')) return 'approved'
  if (eventName.includes('rejected') || eventName.includes('deleted')) return 'rejected'
  if (eventName.includes('submitted')) return 'submitted'
  if (eventName.includes('created')) return 'info'
  return 'neutral'
}

function FilterBar({
  initial,
  onApply,
  onExport,
  isExporting,
}: {
  initial: AuditFilterValues
  onApply: (values: AuditFilterValues) => void
  onExport: (values: AuditFilterValues) => void
  isExporting: boolean
}) {
  const { t } = useTranslation()
  const [values, setValues] = useState<AuditFilterValues>(initial)

  const set = (patch: Partial<AuditFilterValues>): void => {
    setValues((prev) => ({ ...prev, ...patch }))
  }

  const canExport = values.occurredFrom !== '' && values.occurredTo !== ''

  const ENTITY_CHIPS: { value: string; labelKey: MessageKey }[] = [
    { value: '', labelKey: 'audit.filter.allTypes' },
    { value: 'Report', labelKey: 'audit.filter.entityReport' },
    { value: 'User', labelKey: 'audit.filter.entityUser' },
    { value: 'ReportTemplate', labelKey: 'audit.filter.entityTemplate' },
  ]

  const selectEntity = (value: string): void => {
    const next = { ...values, entityType: value }
    setValues(next)
    onApply(next)
  }

  return (
    <div className="flex flex-wrap items-center gap-2">
      {ENTITY_CHIPS.map((c) => (
        <Chip
          key={c.value}
          active={values.entityType === c.value}
          onClick={() => {
            selectEntity(c.value)
          }}
        >
          {t(c.labelKey)}
        </Chip>
      ))}
      <div className="ml-auto flex items-center gap-2">
        <Input
          type="date"
          aria-label={t('audit.filter.occurredFrom')}
          value={values.occurredFrom}
          onChange={(event) => {
            set({ occurredFrom: event.target.value })
          }}
        />
        <Input
          type="date"
          aria-label={t('audit.filter.occurredTo')}
          value={values.occurredTo}
          onChange={(event) => {
            set({ occurredTo: event.target.value })
          }}
        />
        <Button
          variant="ghost"
          disabled={!canExport || isExporting}
          onClick={() => {
            onExport(values)
          }}
        >
          ⬇ {t('audit.export.action')}
        </Button>
      </div>
    </div>
  )
}

function fmt(value: unknown): string {
  if (value === null || value === undefined) return '—'
  if (typeof value === 'string') return value
  return JSON.stringify(value)
}

function DiffModal({ event, onClose }: { event: AuditEvent; onClose: () => void }) {
  const { t } = useTranslation()
  const before = event.before
  const after = event.after
  const keys = [...new Set([...Object.keys(before ?? {}), ...Object.keys(after ?? {})])]
  const created = before === null
  const deleted = after === null

  return (
    <Modal open onClose={onClose} title={t('audit.diff.title')} size="lg">
      <div className="flex flex-col gap-3">
        <div className="flex items-center gap-2">
          <Badge tone={eventTone(event.eventName)}>{event.eventName}</Badge>
          <span className="text-xs text-fg-faint">
            {event.entityType} · {formatJstDateTime(event.occurredAt)}
          </span>
        </div>
        {created && <InlineAlert variant="success">{t('audit.diff.created')}</InlineAlert>}
        {deleted && <InlineAlert variant="error">{t('audit.diff.deleted')}</InlineAlert>}
        {keys.length === 0 ? (
          <p className="text-sm text-fg-faint">{t('audit.diff.noChanges')}</p>
        ) : (
          <div className="overflow-hidden rounded-input border border-border">
            <div className="grid grid-cols-2 border-b border-border bg-surface-overlay text-xs font-semibold text-fg-muted">
              <span className="px-3 py-2">{t('audit.diff.before')}</span>
              <span className="border-l border-border px-3 py-2">{t('audit.diff.after')}</span>
            </div>
            <dl className="divide-y divide-border-2 font-mono text-xs">
              {keys.map((key) => {
                const b = before?.[key]
                const a = after?.[key]
                const changed = fmt(b) !== fmt(a)
                return (
                  <div key={key} className="grid grid-cols-2">
                    <div
                      className={
                        changed && b !== undefined ? 'bg-rejected-soft px-3 py-1.5' : 'px-3 py-1.5'
                      }
                    >
                      <span className="text-fg-faint">{key}: </span>
                      <span className="text-fg">{fmt(b)}</span>
                    </div>
                    <div
                      className={
                        changed && a !== undefined
                          ? 'border-l border-border bg-approved-soft px-3 py-1.5'
                          : 'border-l border-border px-3 py-1.5'
                      }
                    >
                      <span className="text-fg-faint">{key}: </span>
                      <span className="text-fg">{fmt(a)}</span>
                    </div>
                  </div>
                )
              })}
            </dl>
          </div>
        )}
      </div>
    </Modal>
  )
}

export function AuditLog() {
  const { t } = useTranslation()
  const audit = useAuditLog()
  const { exportCsv, isExporting, errorKey: exportErrorKey } = useExportAudit()
  const [selected, setSelected] = useState<AuditEvent | null>(null)

  const from = audit.total === 0 ? 0 : audit.offset + 1
  const to = Math.min(audit.offset + audit.limit, audit.total)

  return (
    <div className="flex flex-col">
      {/* white header bar (full-bleed) */}
      <div className="flex flex-col gap-3 border-b border-border bg-surface-raised px-6 py-4">
        <div className="flex flex-wrap items-center gap-2.5">
          <h2 className="text-lg font-bold text-fg">{t('audit.list.title')}</h2>
          <span className="text-sm text-fg-faint">{t('audit.list.subtitle')}</span>
        </div>
        <FilterBar
          initial={audit.filters}
          onApply={audit.applyFilters}
          onExport={(values) => {
            exportCsv(values.occurredFrom, values.occurredTo, values.entityType)
          }}
          isExporting={isExporting}
        />
      </div>

      <div className="flex flex-col gap-4 px-6 py-4">
        {exportErrorKey !== null && <InlineAlert variant="error">{t(exportErrorKey)}</InlineAlert>}

        {audit.isLoading ? (
          <LoadingState label={t('common.state.loading')} />
        ) : audit.isError ? (
          <ErrorState
            message={t('audit.list.error')}
            retryLabel={t('common.actions.retry')}
            onRetry={audit.refetch}
          />
        ) : audit.events.length === 0 ? (
          <EmptyState message={t('audit.list.empty')} />
        ) : (
          <Stack gap="sm">
            <div className="overflow-hidden rounded-card border border-border bg-surface-raised">
              <TableWrap>
                <Table className="min-w-160">
                  <thead>
                    <Tr>
                      <Th className="w-44">{t('audit.col.occurredAt')}</Th>
                      <Th className="w-40">{t('audit.col.event')}</Th>
                      <Th className="w-32">{t('audit.col.actor')}</Th>
                      <Th>{t('audit.col.entityType')}</Th>
                      <Th className="w-20" />
                    </Tr>
                  </thead>
                  <tbody>
                    {audit.events.map((event) => (
                      <Tr
                        key={event.id}
                        interactive
                        onClick={() => {
                          setSelected(event)
                        }}
                      >
                        <Td className="whitespace-nowrap text-fg-muted tabular-nums">
                          {formatJstDateTime(event.occurredAt)}
                        </Td>
                        <Td>
                          <Badge tone={eventTone(event.eventName)}>{event.eventName}</Badge>
                        </Td>
                        <Td className="text-fg-muted">{event.actorName ?? event.actorId ?? '—'}</Td>
                        <Td className="text-fg-muted">
                          {event.entityType}
                          <span className="block truncate font-mono text-xs text-fg-faint">
                            {event.entityId}
                          </span>
                        </Td>
                        <Td className="text-right">
                          <span className="text-sm font-semibold text-accent">
                            {t('audit.col.diff')} ›
                          </span>
                        </Td>
                      </Tr>
                    ))}
                  </tbody>
                </Table>
              </TableWrap>
            </div>
            <div className="flex items-center justify-between gap-3">
              <Text variant="muted">
                {t('audit.pagination.range', { from, to, total: audit.total })}
              </Text>
              <div className="flex gap-2">
                <Button
                  variant="secondary"
                  size="sm"
                  disabled={audit.offset === 0}
                  onClick={() => {
                    audit.goToOffset(audit.offset - audit.limit)
                  }}
                >
                  {t('common.actions.previous')}
                </Button>
                <Button
                  variant="secondary"
                  size="sm"
                  disabled={audit.offset + audit.limit >= audit.total}
                  onClick={() => {
                    audit.goToOffset(audit.offset + audit.limit)
                  }}
                >
                  {t('common.actions.next')}
                </Button>
              </div>
            </div>
          </Stack>
        )}
      </div>

      {selected !== null && (
        <DiffModal
          event={selected}
          onClose={() => {
            setSelected(null)
          }}
        />
      )}
    </div>
  )
}
