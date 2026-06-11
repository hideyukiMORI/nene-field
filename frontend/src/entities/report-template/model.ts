import type { TemplateId } from './ids'

/** A report template as used by the submission form's picker (UI model). */
export interface Template {
  id: TemplateId
  name: string
  isDefault: boolean
}
