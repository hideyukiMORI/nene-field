import { useState } from 'react'
import { downloadAuditCsv } from '@/entities/audit-event'
import type { MessageKey } from '@/shared/i18n'
import { triggerDownload } from '@/shared/lib/trigger-download'

export interface ExportAuditState {
  exportCsv: (occurredFrom: string, occurredTo: string, entityType: string) => void
  isExporting: boolean
  errorKey: MessageKey | null
}

export function useExportAudit(): ExportAuditState {
  const [isExporting, setExporting] = useState(false)
  const [errorKey, setErrorKey] = useState<MessageKey | null>(null)

  const run = async (
    occurredFrom: string,
    occurredTo: string,
    entityType: string,
  ): Promise<void> => {
    setExporting(true)
    setErrorKey(null)
    try {
      const blob = await downloadAuditCsv({
        occurredFrom,
        occurredTo,
        ...(entityType !== '' ? { entityType } : {}),
      })
      triggerDownload(blob, 'audit_events.csv')
    } catch {
      setErrorKey('audit.export.error')
    } finally {
      setExporting(false)
    }
  }

  return {
    exportCsv: (occurredFrom, occurredTo, entityType) => {
      void run(occurredFrom, occurredTo, entityType)
    },
    isExporting,
    errorKey,
  }
}
