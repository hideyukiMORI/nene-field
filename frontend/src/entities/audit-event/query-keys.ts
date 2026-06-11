export interface AuditEventFilterParams {
  entityType?: string
  eventName?: string
  occurredFrom?: string
  occurredTo?: string
  limit: number
  offset: number
}

export const auditKeys = {
  all: ['audit-events'] as const,
  list: (params: AuditEventFilterParams) => ['audit-events', 'list', params] as const,
}
