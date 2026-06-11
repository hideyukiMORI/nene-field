declare const organizationIdBrand: unique symbol

export type OrganizationId = string & { readonly [organizationIdBrand]: 'OrganizationId' }

export function toOrganizationId(value: string): OrganizationId {
  return value as OrganizationId
}
