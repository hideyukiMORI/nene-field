import type { LoginResponseDto, UserDto } from './api-types'
import { isRole, type Role } from './enum'
import { toUserId } from './ids'
import type { AuthUser, SignInResult } from './model'

function toRole(value: string): Role {
  // Unknown roles are treated as the least-privileged role (fail-closed UI gating;
  // the API is authoritative regardless).
  return isRole(value) ? value : 'submitter'
}

export function toAuthUser(dto: UserDto): AuthUser {
  return {
    id: toUserId(dto.user_id),
    organizationId: dto.organization_id,
    name: dto.name,
    email: dto.email,
    role: toRole(dto.role),
    isActive: dto.is_active,
  }
}

export function toSignInResult(dto: LoginResponseDto): SignInResult {
  return { token: dto.token, user: toAuthUser(dto.user) }
}
