import { describe, expect, it } from 'vitest'
import type { AuditEventDto } from './api-types'
import { toAuditEvent, toAuditEventList } from './mapper'

const dto: AuditEventDto = {
  event_id: 'e-1',
  organization_id: 'org-1',
  entity_type: 'Report',
  entity_id: 'r-1',
  event_name: 'report.approved',
  actor_id: 'u-1',
  actor_name: '管理者',
  before: { status: 'submitted' },
  after: { status: 'approved' },
  request_id: 'req-1',
  occurred_at: '2026-06-11 10:00:00',
}

describe('audit-event mapper', () => {
  it('maps a DTO to the model', () => {
    const event = toAuditEvent(dto)
    expect(event.id).toBe('e-1')
    expect(event.eventName).toBe('report.approved')
    expect(event.actorName).toBe('管理者')
    expect(event.before).toStrictEqual({ status: 'submitted' })
  })

  it('defaults nullable fields', () => {
    const event = toAuditEvent({ ...dto, actor_id: null, actor_name: null, before: null })
    expect(event.actorId).toBeNull()
    expect(event.actorName).toBeNull()
    expect(event.before).toBeNull()
  })

  it('maps the list envelope', () => {
    const list = toAuditEventList({ items: [dto], limit: 20, offset: 0, total: 1 })
    expect(list.total).toBe(1)
    expect(list.items).toHaveLength(1)
  })
})
