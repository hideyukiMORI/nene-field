import { useState } from 'react'
import { downloadAttachmentBlob } from '@/entities/report-attachment'
import type { MessageKey } from '@/shared/i18n'
import { triggerDownload } from '@/shared/lib/trigger-download'

export interface DownloadAttachment {
  download: (attachmentId: string, filename: string) => void
  busyId: string | null
  errorKey: MessageKey | null
}

export function useDownloadAttachment(reportId: string): DownloadAttachment {
  const [busyId, setBusyId] = useState<string | null>(null)
  const [errorKey, setErrorKey] = useState<MessageKey | null>(null)

  const run = async (attachmentId: string, filename: string): Promise<void> => {
    setBusyId(attachmentId)
    setErrorKey(null)
    try {
      const blob = await downloadAttachmentBlob(reportId, attachmentId)
      triggerDownload(blob, filename)
    } catch {
      setErrorKey('report.attachment.downloadError')
    } finally {
      setBusyId(null)
    }
  }

  return {
    download: (attachmentId, filename) => {
      void run(attachmentId, filename)
    },
    busyId,
    errorKey,
  }
}
