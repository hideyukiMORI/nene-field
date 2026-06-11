import { describe, expect, it } from 'vitest'
import type { LoginResponseDto, UserDto } from './api-types'
import { toAuthUser, toSignInResult } from './mapper'

const userDto: UserDto = {
  user_id: 'u-1',
  organization_id: 'org-1',
  name: '田中太郎',
  email: 'tanaka@example.com',
  role: 'admin',
  is_active: true,
  created_at: '2026-06-01 00:00:00',
  updated_at: '2026-06-01 00:00:00',
}

describe('auth mapper', () => {
  it('maps the user DTO to the model', () => {
    const user = toAuthUser(userDto)
    expect(user.id).toBe('u-1')
    expect(user.organizationId).toBe('org-1')
    expect(user.role).toBe('admin')
    expect(user.isActive).toBe(true)
  })

  it('fails closed on an unknown role', () => {
    expect(toAuthUser({ ...userDto, role: 'wizard' }).role).toBe('submitter')
  })

  it('maps the login response', () => {
    const dto: LoginResponseDto = { token: 'jwt-123', user: userDto }
    const result = toSignInResult(dto)
    expect(result.token).toBe('jwt-123')
    expect(result.user.email).toBe('tanaka@example.com')
  })
})
