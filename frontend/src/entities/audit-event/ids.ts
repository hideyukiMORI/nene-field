declare const auditEventIdBrand: unique symbol

export type AuditEventId = string & { readonly [auditEventIdBrand]: 'AuditEventId' }

export function toAuditEventId(value: string): AuditEventId {
  return value as AuditEventId
}
