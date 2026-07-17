import { useMemo, useState } from 'react'
import { useReportListQuery, type ReportStatus } from '@/entities/report'
import { useUserListQuery } from '@/entities/user'
import { useTranslation } from '@/shared/i18n'
import type { MessageKey } from '@/shared/i18n'
import { Button, Field, InlineAlert, Input, Select, useToast } from '@/shared/ui'
import { useExportReports } from '../model/use-export-reports'

// Status presets match the design's single select (not free multi-select).
type StatusPreset = 'approved' | 'submitted_approved' | 'all'

const PRESET_STATUSES: Record<StatusPreset, ReportStatus[]> = {
  approved: ['approved'],
  submitted_approved: ['submitted', 'approved'],
  all: ['draft', 'submitted', 'approved', 'rejected'],
}

const PRESET_LABEL_KEY: Record<StatusPreset, MessageKey> = {
  approved: 'export.status.approvedOnly',
  submitted_approved: 'export.status.submittedApproved',
  all: 'export.status.all',
}

export function ExportReportsForm() {
  const { t } = useTranslation()
  const toast = useToast()
  const { exportCsv, isExporting, errorKey } = useExportReports()
  const users = useUserListQuery({ limit: 100, offset: 0 })
  const reports = useReportListQuery({ limit: 100, offset: 0 })

  const [workDateFrom, setWorkDateFrom] = useState('')
  const [workDateTo, setWorkDateTo] = useState('')
  const [userId, setUserId] = useState('')
  const [projectCode, setProjectCode] = useState('')
  const [statusPreset, setStatusPreset] = useState<StatusPreset>('approved')

  const statuses = PRESET_STATUSES[statusPreset]

  // Live preview against the loaded page (the export endpoint is authoritative).
  const matched = useMemo(() => {
    const allowed = PRESET_STATUSES[statusPreset]
    const items = reports.data?.items ?? []
    return items
      .filter((r) => {
        if (!allowed.includes(r.status)) return false
        if (userId !== '' && r.userId !== userId) return false
        if (projectCode !== '' && (r.projectCode ?? '') !== projectCode) return false
        if (workDateFrom !== '' && r.workDate < workDateFrom) return false
        if (workDateTo !== '' && r.workDate > workDateTo) return false
        return true
      })
      .sort((a, b) => b.workDate.localeCompare(a.workDate))
  }, [reports.data, statusPreset, userId, projectCode, workDateFrom, workDateTo])

  const canExport = workDateFrom !== '' && workDateTo !== '' && !isExporting

  const onDownload = (): void => {
    exportCsv({
      workDateFrom,
      workDateTo,
      statuses,
      ...(userId !== '' ? { userId } : {}),
      ...(projectCode !== '' ? { projectCode } : {}),
    })
    toast.show(t('export.downloaded'))
  }

  return (
    <div className="mx-auto w-full max-w-5xl">
      {/* document header (書類): kicker · 23px title · top-right download w/ count */}
      <div className="mb-6 flex flex-wrap items-end gap-4.5 border-b border-border-hairline pb-4.5">
        <div className="min-w-0">
          <p className="text-xs font-bold tracking-wide text-accent-ink">{t('export.kicker')}</p>
          <h2 className="mt-2 text-doc-title font-bold tracking-tight text-fg">
            {t('export.title')}
          </h2>
          <p className="mt-1.5 max-w-xl text-sm text-fg-muted-2">{t('export.subtitle')}</p>
        </div>
        <div className="flex-1" />
        <Button onClick={onDownload} disabled={!canExport} className="flex-none whitespace-nowrap">
          ⬇ {t('export.download')}
          <span className="ml-2 rounded-pill bg-fg-inverse/20 px-2 py-0.5 tabular-nums">
            {matched.length}
          </span>
        </Button>
      </div>

      {errorKey !== null && (
        <InlineAlert variant="error" className="mb-4">
          {t(errorKey)}
        </InlineAlert>
      )}

      {/* two columns: filters (1fr) + live preview (360px) */}
      <div className="flex flex-col items-start gap-5 lg:flex-row">
        {/* filters */}
        <div className="w-full rounded-2xl border border-border bg-surface-raised p-5.5 lg:flex-1">
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <Field label={t('export.workDateFrom')} htmlFor="export-from">
              <Input
                id="export-from"
                type="date"
                value={workDateFrom}
                onChange={(event) => {
                  setWorkDateFrom(event.target.value)
                }}
              />
            </Field>
            <Field label={t('export.workDateTo')} htmlFor="export-to">
              <Input
                id="export-to"
                type="date"
                value={workDateTo}
                onChange={(event) => {
                  setWorkDateTo(event.target.value)
                }}
              />
            </Field>
          </div>

          <div className="mt-4 flex flex-col gap-4">
            <Field label={t('export.user')} htmlFor="export-user">
              <Select
                id="export-user"
                value={userId}
                onChange={(event) => {
                  setUserId(event.target.value)
                }}
              >
                <option value="">{t('export.allUsers')}</option>
                {(users.data?.items ?? []).map((user) => (
                  <option key={user.id} value={user.id}>
                    {user.name}
                  </option>
                ))}
              </Select>
            </Field>
            <Field label={t('export.statuses')} htmlFor="export-status">
              <Select
                id="export-status"
                value={statusPreset}
                onChange={(event) => {
                  setStatusPreset(event.target.value as StatusPreset)
                }}
              >
                {(['approved', 'submitted_approved', 'all'] as StatusPreset[]).map((preset) => (
                  <option key={preset} value={preset}>
                    {t(PRESET_LABEL_KEY[preset])}
                  </option>
                ))}
              </Select>
            </Field>
            <Field label={t('export.projectCode')} htmlFor="export-project">
              <Input
                id="export-project"
                placeholder="PJ-…"
                value={projectCode}
                onChange={(event) => {
                  setProjectCode(event.target.value)
                }}
              />
            </Field>
          </div>
        </div>

        {/* live preview */}
        <div className="flex w-full flex-col rounded-2xl border border-border bg-surface-raised p-5.5 lg:w-90 lg:flex-none">
          <h3 className="text-sm font-bold text-fg">{t('export.preview.title')}</h3>
          <p className="mt-0.5 text-xs text-fg-faint-2">{t('export.preview.lead')}</p>
          <p className="mt-3.5 mb-4.5 flex items-baseline gap-1.5">
            <span className="text-stat font-bold leading-none text-accent-ink tabular-nums">
              {matched.length}
            </span>
            <span className="text-sm text-fg-muted-2">件</span>
          </p>

          <p className="mb-2 text-xs font-semibold tracking-wide text-fg-faint-2">
            {t('export.preview.leading')}
          </p>
          <div className="flex flex-col gap-2">
            {matched.length === 0 ? (
              <p className="text-sm text-fg-faint">{t('export.preview.empty')}</p>
            ) : (
              matched.slice(0, 5).map((r) => (
                <div
                  key={r.id}
                  className="flex items-center gap-2 rounded-input bg-surface-overlay px-3 py-2 text-xs"
                >
                  <span className="flex-none text-fg-faint-2 tabular-nums">{r.workDate}</span>
                  <span className="min-w-0 flex-1 truncate font-semibold text-fg">{r.title}</span>
                  <span className="flex-none text-fg-muted-2">{r.userName}</span>
                </div>
              ))
            )}
          </div>

          <p className="mt-4.5 border-t border-dashed border-border pt-3.5 text-center text-xs leading-relaxed text-fg-faint">
            {t('export.preview.columns')}
          </p>
        </div>
      </div>
    </div>
  )
}
