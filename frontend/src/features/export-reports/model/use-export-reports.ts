import { useState } from 'react'
import { downloadReportsCsv, type ReportExportParams } from '@/entities/report'
import type { MessageKey } from '@/shared/i18n'
import { triggerDownload } from '@/shared/lib/trigger-download'

export interface ExportReportsState {
  exportCsv: (params: ReportExportParams) => void
  isExporting: boolean
  errorKey: MessageKey | null
}

export function useExportReports(): ExportReportsState {
  const [isExporting, setExporting] = useState(false)
  const [errorKey, setErrorKey] = useState<MessageKey | null>(null)

  const run = async (params: ReportExportParams): Promise<void> => {
    setExporting(true)
    setErrorKey(null)
    try {
      const blob = await downloadReportsCsv(params)
      triggerDownload(blob, `reports_${params.workDateFrom}_${params.workDateTo}.csv`)
    } catch {
      setErrorKey('export.error')
    } finally {
      setExporting(false)
    }
  }

  return {
    exportCsv: (params) => {
      void run(params)
    },
    isExporting,
    errorKey,
  }
}
