/** Wire DTOs (snake_case) for templates — see docs/openapi/openapi.yaml. */

export interface TemplateFieldDto {
  name: string
  label: string
  type: string
  required: boolean
  options?: string[]
}

export interface TemplateDto {
  template_id: string
  organization_id: string
  name: string
  description?: string | null
  fields: TemplateFieldDto[]
  is_default: boolean
  created_at: string
  updated_at: string
}

export interface TemplateListResponseDto {
  items: TemplateDto[]
}
