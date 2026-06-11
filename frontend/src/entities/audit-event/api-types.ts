/** Wire DTOs (snake_case) for audit events — see docs/openapi/openapi.yaml. */

export interface AuditEventDto {
  event_id: string
  organization_id: string
  entity_type: string
  entity_id: string
  event_name: string
  actor_id?: string | null
  actor_name?: string | null
  before?: Record<string, unknown> | null
  after?: Record<string, unknown> | null
  request_id?: string | null
  occurred_at: string
}

export interface AuditEventListResponseDto {
  items: AuditEventDto[]
  limit: number
  offset: number
  total?: number
}
