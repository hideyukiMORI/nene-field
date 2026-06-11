import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient } from '@/shared/api/client'
import type { AppError } from '@/shared/api/errors'
import type { AuditEventListResponseDto } from './api-types'
import { toAuditEventList } from './mapper'
import type { AuditEventList } from './model'
import { auditKeys, type AuditEventFilterParams } from './query-keys'

function buildQuery(params: AuditEventFilterParams): string {
  const search = new URLSearchParams()
  if (params.entityType !== undefined) search.set('entity_type', params.entityType)
  if (params.eventName !== undefined) search.set('event_name', params.eventName)
  if (params.occurredFrom !== undefined) search.set('occurred_from', params.occurredFrom)
  if (params.occurredTo !== undefined) search.set('occurred_to', params.occurredTo)
  search.set('limit', String(params.limit))
  search.set('offset', String(params.offset))
  return search.toString()
}

export function useAuditEventListQuery(
  params: AuditEventFilterParams,
): UseQueryResult<AuditEventList, AppError> {
  return useQuery<AuditEventList, AppError>({
    queryKey: auditKeys.list(params),
    queryFn: async () => {
      const dto = await apiClient.get<AuditEventListResponseDto>(
        `/audit-events?${buildQuery(params)}`,
      )
      return toAuditEventList(dto)
    },
  })
}
