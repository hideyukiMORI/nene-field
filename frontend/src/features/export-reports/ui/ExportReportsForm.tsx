import { useState } from 'react'
import { REPORT_STATUSES, type ReportStatus } from '@/entities/report'
import { useUserListQuery } from '@/entities/user'
import { useTranslation } from '@/shared/i18n'
import type { MessageKey } from '@/shared/i18n'
import { Button, Field, InlineAlert, Input, Select, Stack, Text } from '@/shared/ui'
import { useExportReports } from '../hooks/use-export-reports'

const statusLabelKey: Record<ReportStatus, MessageKey> = {
  draft: 'report.status.draft',
  submitted: 'report.status.submitted',
  approved: 'report.status.approved',
  rejected: 'report.status.rejected',
}

export function ExportReportsForm() {
  const { t } = useTranslation()
  const { exportCsv, isExporting, errorKey } = useExportReports()
  const users = useUserListQuery({ limit: 100, offset: 0 })

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

  const canExport = workDateFrom !== '' && workDateTo !== '' && !isExporting

  return (
    <Stack gap="md">
      <Stack gap="sm">
        <Text variant="title" as="h2">
          {t('export.title')}
        </Text>
        <Text variant="subtitle">{t('export.subtitle')}</Text>
      </Stack>

      {errorKey !== null && <InlineAlert variant="error">{t(errorKey)}</InlineAlert>}

      <div className="border border-border bg-surface-raised p-4">
        <Stack gap="md">
          <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
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

          <fieldset>
            <legend className="mb-1 text-sm font-medium text-fg">{t('export.statuses')}</legend>
            <div className="flex flex-wrap gap-4">
              {REPORT_STATUSES.map((status) => (
                <label key={status} className="flex items-center gap-2 text-sm text-fg">
                  <input
                    type="checkbox"
                    checked={statuses.includes(status)}
                    onChange={() => {
                      toggleStatus(status)
                    }}
                  />
                  {t(statusLabelKey[status])}
                </label>
              ))}
            </div>
          </fieldset>

          <div className="flex flex-wrap items-center gap-2">
            <Button
              disabled={!canExport}
              onClick={() => {
                exportCsv({
                  workDateFrom,
                  workDateTo,
                  statuses,
                  ...(userId !== '' ? { userId } : {}),
                  ...(projectCode !== '' ? { projectCode } : {}),
                })
              }}
            >
              {t('export.download')}
            </Button>
            {(workDateFrom === '' || workDateTo === '') && (
              <span className="text-xs text-fg-muted">{t('export.hint')}</span>
            )}
          </div>
        </Stack>
      </div>
    </Stack>
  )
}
