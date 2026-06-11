export const ROLES = ['submitter', 'approver', 'admin', 'superadmin'] as const

export type Role = (typeof ROLES)[number]

export function isRole(value: string): value is Role {
  return (ROLES as readonly string[]).includes(value)
}

/** Organization management (users, templates, settings, export, audit). */
export function canManageOrganization(role: Role): boolean {
  return role === 'admin' || role === 'superadmin'
}

/** Approve / reject submitted reports. */
export function canApprove(role: Role): boolean {
  return role === 'approver' || canManageOrganization(role)
}
