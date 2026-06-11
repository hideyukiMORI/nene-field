import { useSearchParams } from 'react-router-dom'
import {
  useAuditEventListQuery,
  type AuditEvent,
  type AuditEventFilterParams,
} from '@/entities/audit-event'

const PAGE_SIZE = 20

export interface AuditFilterValues {
  entityType: string
  eventName: string
  occurredFrom: string
  occurredTo: string
}

export interface AuditLogState {
  events: AuditEvent[]
  total: number
  limit: number
  offset: number
  filters: AuditFilterValues
  isLoading: boolean
  isError: boolean
  refetch: () => void
  applyFilters: (values: AuditFilterValues) => void
  goToOffset: (offset: number) => void
}

function readFilters(searchParams: URLSearchParams): AuditFilterValues {
  return {
    entityType: searchParams.get('entity_type') ?? '',
    eventName: searchParams.get('event_name') ?? '',
    occurredFrom: searchParams.get('occurred_from') ?? '',
    occurredTo: searchParams.get('occurred_to') ?? '',
  }
}

function toQueryParams(filters: AuditFilterValues, offset: number): AuditEventFilterParams {
  const params: AuditEventFilterParams = { limit: PAGE_SIZE, offset }
  if (filters.entityType !== '') params.entityType = filters.entityType
  if (filters.eventName !== '') params.eventName = filters.eventName
  if (filters.occurredFrom !== '') params.occurredFrom = filters.occurredFrom
  if (filters.occurredTo !== '') params.occurredTo = filters.occurredTo
  return params
}

function toSearch(filters: AuditFilterValues, offset: number): URLSearchParams {
  const search = new URLSearchParams()
  if (filters.entityType !== '') search.set('entity_type', filters.entityType)
  if (filters.eventName !== '') search.set('event_name', filters.eventName)
  if (filters.occurredFrom !== '') search.set('occurred_from', filters.occurredFrom)
  if (filters.occurredTo !== '') search.set('occurred_to', filters.occurredTo)
  if (offset > 0) search.set('offset', String(offset))
  return search
}

export function useAuditLog(): AuditLogState {
  const [searchParams, setSearchParams] = useSearchParams()
  const filters = readFilters(searchParams)
  const offset = Math.max(0, Number(searchParams.get('offset') ?? '0') || 0)

  const query = useAuditEventListQuery(toQueryParams(filters, offset))

  return {
    events: query.data?.items ?? [],
    total: query.data?.total ?? 0,
    limit: PAGE_SIZE,
    offset,
    filters,
    isLoading: query.isLoading,
    isError: query.isError,
    refetch: () => {
      void query.refetch()
    },
    applyFilters: (values) => {
      setSearchParams(toSearch(values, 0))
    },
    goToOffset: (next) => {
      setSearchParams(toSearch(filters, Math.max(0, next)))
    },
  }
}
