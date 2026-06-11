/** Every role a user may hold (display). `superadmin` is provisioned out-of-band. */
export const USER_ROLES = ['submitter', 'approver', 'admin', 'superadmin'] as const

export type UserRole = (typeof USER_ROLES)[number]

/** Roles assignable through the users API (OpenAPI enum — no superadmin). */
export const ASSIGNABLE_USER_ROLES = ['submitter', 'approver', 'admin'] as const

export type AssignableUserRole = (typeof ASSIGNABLE_USER_ROLES)[number]

export function isUserRole(value: string): value is UserRole {
  return (USER_ROLES as readonly string[]).includes(value)
}
