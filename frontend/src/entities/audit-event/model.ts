import type { AuditEventId } from './ids'

/** One immutable audit-trail entry (UI model). */
export interface AuditEvent {
  id: AuditEventId
  entityType: string
  entityId: string
  eventName: string
  actorId: string | null
  actorName: string | null
  before: Record<string, unknown> | null
  after: Record<string, unknown> | null
  requestId: string | null
  occurredAt: string
}

export interface AuditEventList {
  items: AuditEvent[]
  limit: number
  offset: number
  total: number
}
