/** Wire DTOs (snake_case) for templates — see docs/openapi/openapi.yaml. */

export interface TemplateDto {
  template_id: string
  name: string
  is_default: boolean
}

export interface TemplateListResponseDto {
  items: TemplateDto[]
}
