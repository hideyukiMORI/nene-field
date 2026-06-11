import type { TemplateDto, TemplateListResponseDto } from './api-types'
import { toTemplateId } from './ids'
import type { Template } from './model'

export function toTemplate(dto: TemplateDto): Template {
  return {
    id: toTemplateId(dto.template_id),
    name: dto.name,
    isDefault: dto.is_default,
  }
}

export function toTemplateList(dto: TemplateListResponseDto): Template[] {
  return dto.items.map(toTemplate)
}
