import { apiClient } from '@/shared/api/client'

export interface AuditExportParams {
  occurredFrom: string
  occurredTo: string
  entityType?: string
}

/** Downloads the audit-event CSV (admin; the export itself is audited server-side). */
export function downloadAuditCsv(params: AuditExportParams): Promise<Blob> {
  const search = new URLSearchParams({
    occurred_from: params.occurredFrom,
    occurred_to: params.occurredTo,
  })
  if (params.entityType !== undefined) search.set('entity_type', params.entityType)
  return apiClient.getBlob(`/audit-events/export?${search.toString()}`)
}
