import type { UserDto, UserListResponseDto } from './api-types'
import { isUserRole, type UserRole } from './enum'
import { toUserId } from './ids'
import type { User, UserList } from './model'

function toRole(value: string): UserRole {
  return isUserRole(value) ? value : 'submitter'
}

export function toUser(dto: UserDto): User {
  return {
    id: toUserId(dto.user_id),
    organizationId: dto.organization_id,
    name: dto.name,
    email: dto.email,
    role: toRole(dto.role),
    isActive: dto.is_active,
    createdAt: dto.created_at,
    updatedAt: dto.updated_at,
  }
}

export function toUserList(dto: UserListResponseDto): UserList {
  return {
    items: dto.items.map(toUser),
    limit: dto.limit,
    offset: dto.offset,
    total: dto.total ?? dto.items.length,
  }
}
