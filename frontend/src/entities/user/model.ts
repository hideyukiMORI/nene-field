import type { UserRole } from './enum'
import type { UserId } from './ids'

/** An operator account (UI model). */
export interface User {
  id: UserId
  organizationId: string
  name: string
  email: string
  role: UserRole
  isActive: boolean
  createdAt: string
  updatedAt: string
}

export interface UserList {
  items: User[]
  limit: number
  offset: number
  total: number
}
