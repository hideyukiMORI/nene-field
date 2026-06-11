export const TEMPLATE_FIELD_TYPES = [
  'text',
  'textarea',
  'number',
  'checkbox',
  'date',
  'select',
] as const

export type TemplateFieldType = (typeof TEMPLATE_FIELD_TYPES)[number]

export function isTemplateFieldType(value: string): value is TemplateFieldType {
  return (TEMPLATE_FIELD_TYPES as readonly string[]).includes(value)
}
