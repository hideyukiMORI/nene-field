import type { TemplateDto, TemplateFieldDto, TemplateListResponseDto } from './api-types'
import { isTemplateFieldType, type TemplateFieldType } from './enum'
import { toTemplateId } from './ids'
import type { Template, TemplateField } from './model'

function toFieldType(value: string): TemplateFieldType {
  return isTemplateFieldType(value) ? value : 'text'
}

function toTemplateField(dto: TemplateFieldDto): TemplateField {
  return {
    name: dto.name,
    label: dto.label,
    type: toFieldType(dto.type),
    required: dto.required,
    options: dto.options ?? [],
  }
}

export function toTemplate(dto: TemplateDto): Template {
  return {
    id: toTemplateId(dto.template_id),
    name: dto.name,
    description: dto.description ?? null,
    fields: dto.fields.map(toTemplateField),
    isDefault: dto.is_default,
    createdAt: dto.created_at,
    updatedAt: dto.updated_at,
  }
}

export function toTemplateList(dto: TemplateListResponseDto): Template[] {
  return dto.items.map(toTemplate)
}
