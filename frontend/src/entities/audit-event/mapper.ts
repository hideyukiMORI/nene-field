import type { AuditEventDto, AuditEventListResponseDto } from './api-types'
import { toAuditEventId } from './ids'
import type { AuditEvent, AuditEventList } from './model'

export function toAuditEvent(dto: AuditEventDto): AuditEvent {
  return {
    id: toAuditEventId(dto.event_id),
    entityType: dto.entity_type,
    entityId: dto.entity_id,
    eventName: dto.event_name,
    actorId: dto.actor_id ?? null,
    actorName: dto.actor_name ?? null,
    before: dto.before ?? null,
    after: dto.after ?? null,
    requestId: dto.request_id ?? null,
    occurredAt: dto.occurred_at,
  }
}

export function toAuditEventList(dto: AuditEventListResponseDto): AuditEventList {
  return {
    items: dto.items.map(toAuditEvent),
    limit: dto.limit,
    offset: dto.offset,
    total: dto.total ?? dto.items.length,
  }
}
