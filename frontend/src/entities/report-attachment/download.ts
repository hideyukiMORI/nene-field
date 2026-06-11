import { apiClient } from '@/shared/api/client'

/** Fetches an attachment's bytes (authenticated, SHA-256 verified server-side). */
export function downloadAttachmentBlob(reportId: string, attachmentId: string): Promise<Blob> {
  return apiClient.getBlob(
    `/reports/${encodeURIComponent(reportId)}/attachments/${encodeURIComponent(attachmentId)}`,
  )
}
