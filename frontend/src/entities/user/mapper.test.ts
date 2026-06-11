import { describe, expect, it } from 'vitest'
import type { UserDto } from './api-types'
import { toUser, toUserList } from './mapper'

const dto: UserDto = {
  user_id: 'u-1',
  organization_id: 'org-1',
  name: '田中太郎',
  email: 'tanaka@example.com',
  role: 'approver',
  is_active: true,
  created_at: '2026-06-01 00:00:00',
  updated_at: '2026-06-01 00:00:00',
}

describe('user mapper', () => {
  it('maps a user DTO to the model', () => {
    const user = toUser(dto)
    expect(user.id).toBe('u-1')
    expect(user.email).toBe('tanaka@example.com')
    expect(user.role).toBe('approver')
    expect(user.isActive).toBe(true)
  })

  it('keeps superadmin for display and fails closed on an unknown role', () => {
    expect(toUser({ ...dto, role: 'superadmin' }).role).toBe('superadmin')
    expect(toUser({ ...dto, role: 'wizard' }).role).toBe('submitter')
  })

  it('maps the list envelope', () => {
    const list = toUserList({ items: [dto], limit: 100, offset: 0, total: 1 })
    expect(list.total).toBe(1)
    expect(list.items).toHaveLength(1)
  })
})
