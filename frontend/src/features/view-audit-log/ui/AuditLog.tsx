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
import { useAuditLog, type AuditFilterValues } from '../model/use-audit-log'
import { useExportAudit } from '../model/use-export-audit'

type BadgeTone = 'approved' | 'rejected' | 'submitted' | 'info' | 'neutral'

function eventTone(eventName: string): BadgeTone {
  if (eventName.includes('approved')) return 'approved'
  if (eventName.includes('rejected') || eventName.includes('deleted')) return 'rejected'
  if (eventName.includes('submitted')) return 'submitted'
  if (eventName.includes('created')) return 'info'
  return 'neutral'
}

/**
 * Natural-language labels for raw audit event names (`<entity>.<action>`), so the
 * log reads for general users rather than developers. Unknown events fall back to
 * the raw name. Keep in sync with the backend use cases that emit these names.
 */
const EVENT_LABEL_KEY: Record<string, MessageKey> = {
  'report.created': 'audit.event.report.created',
  'report.submitted': 'audit.event.report.submitted',
  'report.approved': 'audit.event.report.approved',
  'report.rejected': 'audit.event.report.rejected',
  'report.deleted': 'audit.event.report.deleted',
  'report.exported': 'audit.event.report.exported',
  'user.created': 'audit.event.user.created',
  'user.updated': 'audit.event.user.updated',
  'user.deleted': 'audit.event.user.deleted',
  'template.created': 'audit.event.template.created',
  'template.updated': 'audit.event.template.updated',
  'template.deleted': 'audit.event.template.deleted',
  'organization.created': 'audit.event.organization.created',
  'organization.updated': 'audit.event.organization.updated',
  'attachment.deleted': 'audit.event.attachment.deleted',
  'audit.exported': 'audit.event.audit.exported',
}

function eventLabel(t: (key: MessageKey) => string, eventName: string): string {
  const key = EVENT_LABEL_KEY[eventName]
  return key !== undefined ? t(key) : eventName
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
      <div className="ml-auto flex flex-wrap items-center gap-2">
        <div className="w-40 flex-none">
          <Input
            type="date"
            aria-label={t('audit.filter.occurredFrom')}
            value={values.occurredFrom}
            onChange={(event) => {
              set({ occurredFrom: event.target.value })
            }}
          />
        </div>
        <div className="w-40 flex-none">
          <Input
            type="date"
            aria-label={t('audit.filter.occurredTo')}
            value={values.occurredTo}
            onChange={(event) => {
              set({ occurredTo: event.target.value })
            }}
          />
        </div>
        <Button
          variant="ghost"
          size="sm"
          disabled={!canExport || isExporting}
          onClick={() => {
            onExport(values)
          }}
          className="flex-none whitespace-nowrap"
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
    <Modal
      open
      onClose={onClose}
      title={t('audit.diff.title')}
      closeLabel={t('common.actions.close')}
      size="lg"
    >
      <div className="flex flex-col gap-3">
        <div className="flex items-center gap-2">
          <Badge tone={eventTone(event.eventName)}>{eventLabel(t, event.eventName)}</Badge>
          <span className="text-xs text-text-faint">
            {event.entityType} · {formatJstDateTime(event.occurredAt)}
          </span>
        </div>
        {created && <InlineAlert variant="success">{t('audit.diff.created')}</InlineAlert>}
        {deleted && <InlineAlert variant="error">{t('audit.diff.deleted')}</InlineAlert>}
        {keys.length === 0 ? (
          <p className="text-sm text-text-faint">{t('audit.diff.noChanges')}</p>
        ) : (
          <div className="overflow-hidden rounded-x-input border border-border">
            <div className="grid grid-cols-2 border-b border-border bg-surface-overlay text-xs font-semibold text-text-muted">
              <span className="px-3 py-2">{t('audit.diff.before')}</span>
              <span className="border-l border-border px-3 py-2">{t('audit.diff.after')}</span>
            </div>
            <dl className="divide-y divide-border font-mono text-xs">
              {keys.map((key) => {
                const b = before?.[key]
                const a = after?.[key]
                const changed = fmt(b) !== fmt(a)
                return (
                  <div key={key} className="grid grid-cols-2">
                    <div
                      className={
                        changed && b !== undefined ? 'bg-x-rejected-soft px-3 py-1.5' : 'px-3 py-1.5'
                      }
                    >
                      <span className="text-text-faint">{key}: </span>
                      <span className="text-text-primary">{fmt(b)}</span>
                    </div>
                    <div
                      className={
                        changed && a !== undefined
                          ? 'border-l border-border bg-x-approved-soft px-3 py-1.5'
                          : 'border-l border-border px-3 py-1.5'
                      }
                    >
                      <span className="text-text-faint">{key}: </span>
                      <span className="text-text-primary">{fmt(a)}</span>
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
    <div className="flex h-full flex-col">
      {/* pinned toolbar (作業卓): flex-none white bar, table below scrolls */}
      <div className="relative z-10 flex flex-none flex-col gap-3 border-b border-border bg-surface-raised px-6.5 py-4 shadow-x-toolbar">
        <div className="flex flex-wrap items-center gap-2.5">
          <h2 className="text-lg font-bold text-text-primary">{t('audit.list.title')}</h2>
          <span className="text-sm text-text-faint">{t('audit.list.subtitle')}</span>
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

      <div className="flex min-h-0 flex-1 flex-col gap-4 overflow-y-auto px-6.5 py-4">
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
            <TableWrap>
              <Table className="min-w-170">
                <thead>
                  <Tr>
                    <Th className="w-40">{t('audit.col.occurredAt')}</Th>
                    <Th className="w-40">{t('audit.col.event')}</Th>
                    <Th className="w-40">{t('audit.col.actor')}</Th>
                    <Th>{t('audit.col.entityType')}</Th>
                    <Th className="w-20 text-right">{t('audit.col.diff')}</Th>
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
                      <Td className="whitespace-nowrap text-text-muted tabular-nums">
                        {formatJstDateTime(event.occurredAt)}
                      </Td>
                      <Td>
                        <Badge tone={eventTone(event.eventName)}>
                          {eventLabel(t, event.eventName)}
                        </Badge>
                      </Td>
                      <Td>
                        <div className="flex items-center gap-2">
                          <span className="grid h-6.5 w-6.5 flex-none place-items-center rounded-x-pill bg-accent-soft text-caption font-bold text-on-accent">
                            {(event.actorName ?? event.actorId ?? '—').slice(0, 1)}
                          </span>
                          <span className="whitespace-nowrap font-semibold text-text-primary">
                            {event.actorName ?? event.actorId ?? '—'}
                          </span>
                        </div>
                      </Td>
                      <Td className="truncate font-mono text-label text-text-muted">
                        {event.entityType} · {event.entityId}
                      </Td>
                      <Td className="text-right">
                        <span className="text-label font-bold text-on-accent">
                          {t('audit.col.diff')} ›
                        </span>
                      </Td>
                    </Tr>
                  ))}
                </tbody>
              </Table>
            </TableWrap>
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
