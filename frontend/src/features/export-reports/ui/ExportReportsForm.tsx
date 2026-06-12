import { useMemo, useState } from 'react'
import { REPORT_STATUSES, useReportListQuery, type ReportStatus } from '@/entities/report'
import { useUserListQuery } from '@/entities/user'
import { useTranslation } from '@/shared/i18n'
import type { MessageKey } from '@/shared/i18n'
import {
  Button,
  Card,
  Chip,
  Field,
  InlineAlert,
  Input,
  Select,
  Stack,
  Text,
  useToast,
} from '@/shared/ui'
import { useExportReports } from '../hooks/use-export-reports'

const statusLabelKey: Record<ReportStatus, MessageKey> = {
  draft: 'report.status.draft',
  submitted: 'report.status.submitted',
  approved: 'report.status.approved',
  rejected: 'report.status.rejected',
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
  const [statuses, setStatuses] = useState<ReportStatus[]>(['approved'])

  const toggleStatus = (status: ReportStatus): void => {
    setStatuses((prev) =>
      prev.includes(status) ? prev.filter((value) => value !== status) : [...prev, status],
    )
  }

  // Live preview against the loaded page (the export endpoint is authoritative).
  const matched = useMemo(() => {
    const items = reports.data?.items ?? []
    return items.filter((r) => {
      if (statuses.length > 0 && !statuses.includes(r.status)) return false
      if (userId !== '' && r.userId !== userId) return false
      if (projectCode !== '' && (r.projectCode ?? '') !== projectCode) return false
      if (workDateFrom !== '' && r.workDate < workDateFrom) return false
      if (workDateTo !== '' && r.workDate > workDateTo) return false
      return true
    })
  }, [reports.data, statuses, userId, projectCode, workDateFrom, workDateTo])

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
    <Stack gap="md">
      <Stack gap="sm">
        <Text variant="title" as="h2">
          {t('export.title')}
        </Text>
        <Text variant="subtitle">{t('export.subtitle')}</Text>
      </Stack>

      {errorKey !== null && <InlineAlert variant="error">{t(errorKey)}</InlineAlert>}

      <div className="grid gap-4 lg:grid-cols-2">
        {/* filters */}
        <Card>
          <Stack gap="md">
            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
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
              <Field label={t('export.projectCode')} htmlFor="export-project">
                <Input
                  id="export-project"
                  value={projectCode}
                  onChange={(event) => {
                    setProjectCode(event.target.value)
                  }}
                />
              </Field>
            </div>

            <div>
              <span className="mb-2 block text-sm font-medium text-fg">{t('export.statuses')}</span>
              <div className="flex flex-wrap gap-1.5">
                {REPORT_STATUSES.map((status) => (
                  <Chip
                    key={status}
                    active={statuses.includes(status)}
                    onClick={() => {
                      toggleStatus(status)
                    }}
                  >
                    {t(statusLabelKey[status])}
                  </Chip>
                ))}
              </div>
            </div>
          </Stack>
        </Card>

        {/* live preview + download */}
        <Card>
          <h3 className="text-sm font-bold text-fg">{t('export.preview.title')}</h3>
          <p className="mt-0.5 text-xs text-fg-muted">{t('export.preview.lead')}</p>
          <p className="mt-1 text-3xl font-extrabold text-accent-ink tabular-nums">
            {matched.length} <span className="text-base font-bold">件</span>
          </p>

          <p className="mt-4 mb-2 text-xs font-semibold text-fg-faint">
            {t('export.preview.leading')}
          </p>
          <div className="flex flex-col gap-2">
            {matched.length === 0 ? (
              <p className="text-sm text-fg-faint">{t('export.preview.empty')}</p>
            ) : (
              matched.slice(0, 5).map((r) => (
                <div
                  key={r.id}
                  className="flex items-center justify-between gap-2 rounded-input bg-surface-overlay px-3 py-2 text-sm"
                >
                  <span className="truncate text-fg">{r.title}</span>
                  <span className="flex-none text-xs text-fg-faint tabular-nums">{r.workDate}</span>
                </div>
              ))
            )}
          </div>

          <Button disabled={!canExport} onClick={onDownload} className="mt-4 w-full">
            ⬇ {t('export.download')}
          </Button>
          {(workDateFrom === '' || workDateTo === '') && (
            <p className="mt-1.5 text-center text-xs text-fg-muted">{t('export.hint')}</p>
          )}
          <p className="mt-3 text-xs text-fg-faint">{t('export.preview.columns')}</p>
        </Card>
      </div>
    </Stack>
  )
}
