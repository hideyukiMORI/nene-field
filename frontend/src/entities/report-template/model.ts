import type { TemplateFieldType } from './enum'
import type { TemplateId } from './ids'

export interface TemplateField {
  name: string
  label: string
  type: TemplateFieldType
  required: boolean
  options: string[]
}

/** A report template (UI model). */
export interface Template {
  id: TemplateId
  name: string
  description: string | null
  fields: TemplateField[]
  isDefault: boolean
  createdAt: string
  updatedAt: string
}
