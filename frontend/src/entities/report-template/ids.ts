declare const templateIdBrand: unique symbol

export type TemplateId = string & { readonly [templateIdBrand]: 'TemplateId' }

export function toTemplateId(value: string): TemplateId {
  return value as TemplateId
}
