import type { Role } from './enum'
import type { UserId } from './ids'

/** The authenticated principal (UI model). */
export interface AuthUser {
  id: UserId
  organizationId: string
  name: string
  email: string
  role: Role
  isActive: boolean
}

export interface SignInResult {
  token: string
  user: AuthUser
}
