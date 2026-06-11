import { useState } from 'react'
import type { AuditEvent } from '@/entities/audit-event'
import { useTranslation } from '@/shared/i18n'
import { formatJstDateTime } from '@/shared/lib/format-date'
import {
  Button,
  EmptyState,
  ErrorState,
  Field,
  InlineAlert,
  Input,
  LoadingState,
  Select,
  Stack,
  Text,
} from '@/shared/ui'
import { useAuditLog, type AuditFilterValues } from '../hooks/use-audit-log'
import { useExportAudit } from '../hooks/use-export-audit'

const ENTITY_TYPES = [
  'Report',
  'ReportTemplate',
  'ReportAttachment',
  'User',
  'Organization',
  'AuditEvent',
]

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

  return (
    <div className="border border-border bg-surface-raised p-4">
      <div className="grid grid-cols-2 gap-3 md:grid-cols-4">
        <Field label={t('audit.filter.entityType')} htmlFor="audit-entity-type">
          <Select
            id="audit-entity-type"
            value={values.entityType}
            onChange={(event) => {
              set({ entityType: event.target.value })
            }}
          >
            <option value="">{t('audit.filter.allTypes')}</option>
            {ENTITY_TYPES.map((type) => (
              <option key={type} value={type}>
                {type}
              </option>
            ))}
          </Select>
        </Field>
        <Field label={t('audit.filter.eventName')} htmlFor="audit-event-name">
          <Input
            id="audit-event-name"
            value={values.eventName}
            onChange={(event) => {
              set({ eventName: event.target.value })
            }}
          />
        </Field>
        <Field label={t('audit.filter.occurredFrom')} htmlFor="audit-from">
          <Input
            id="audit-from"
            type="date"
            value={values.occurredFrom}
            onChange={(event) => {
              set({ occurredFrom: event.target.value })
            }}
          />
        </Field>
        <Field label={t('audit.filter.occurredTo')} htmlFor="audit-to">
          <Input
            id="audit-to"
            type="date"
            value={values.occurredTo}
            onChange={(event) => {
              set({ occurredTo: event.target.value })
            }}
          />
        </Field>
      </div>
      <div className="mt-3 flex flex-wrap items-center gap-2">
        <Button
          onClick={() => {
            onApply(values)
          }}
        >
          {t('common.actions.apply')}
        </Button>
        <Button
          variant="secondary"
          onClick={() => {
            const cleared: AuditFilterValues = {
              entityType: '',
              eventName: '',
              occurredFrom: '',
              occurredTo: '',
            }
            setValues(cleared)
            onApply(cleared)
          }}
        >
          {t('common.actions.clear')}
        </Button>
        <Button
          variant="secondary"
          disabled={!canExport || isExporting}
          onClick={() => {
            onExport(values)
          }}
        >
          {t('audit.export.action')}
        </Button>
        {!canExport && <span className="text-xs text-fg-muted">{t('audit.export.hint')}</span>}
      </div>
    </div>
  )
}

function AuditTable({ events }: { events: AuditEvent[] }) {
  const { t } = useTranslation()
  return (
    <div className="overflow-x-auto border border-border bg-surface-raised">
      <table className="w-full text-sm">
        <thead>
          <tr className="border-b border-border-strong text-left text-fg-muted">
            <th scope="col" className="px-3 py-2 font-semibold">
              {t('audit.col.occurredAt')}
            </th>
            <th scope="col" className="px-3 py-2 font-semibold">
              {t('audit.col.event')}
            </th>
            <th scope="col" className="px-3 py-2 font-semibold">
              {t('audit.col.entityType')}
            </th>
            <th scope="col" className="px-3 py-2 font-semibold">
              {t('audit.col.entityId')}
            </th>
            <th scope="col" className="px-3 py-2 font-semibold">
              {t('audit.col.actor')}
            </th>
          </tr>
        </thead>
        <tbody>
          {events.map((event) => (
            <tr key={event.id} className="border-b border-border">
              <td className="px-3 py-2 whitespace-nowrap text-fg-muted">
                {formatJstDateTime(event.occurredAt)}
              </td>
              <td className="px-3 py-2 text-fg">{event.eventName}</td>
              <td className="px-3 py-2 text-fg-muted">{event.entityType}</td>
              <td className="px-3 py-2 font-mono text-xs text-fg-muted">{event.entityId}</td>
              <td className="px-3 py-2 text-fg-muted">{event.actorName ?? event.actorId ?? '—'}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  )
}

export function AuditLog() {
  const { t } = useTranslation()
  const audit = useAuditLog()
  const { exportCsv, isExporting, errorKey: exportErrorKey } = useExportAudit()

  const from = audit.total === 0 ? 0 : audit.offset + 1
  const to = Math.min(audit.offset + audit.limit, audit.total)

  return (
    <Stack gap="md">
      <FilterBar
        initial={audit.filters}
        onApply={audit.applyFilters}
        onExport={(values) => {
          exportCsv(values.occurredFrom, values.occurredTo, values.entityType)
        }}
        isExporting={isExporting}
      />

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
          <AuditTable events={audit.events} />
          <div className="flex items-center justify-between gap-3">
            <Text variant="muted">
              {t('audit.pagination.range', { from, to, total: audit.total })}
            </Text>
            <div className="flex gap-2">
              <Button
                variant="secondary"
                disabled={audit.offset === 0}
                onClick={() => {
                  audit.goToOffset(audit.offset - audit.limit)
                }}
              >
                {t('common.actions.previous')}
              </Button>
              <Button
                variant="secondary"
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
    </Stack>
  )
}
