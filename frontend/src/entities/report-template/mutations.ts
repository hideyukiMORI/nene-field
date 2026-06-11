import { useMutation, useQueryClient, type UseMutationResult } from '@tanstack/react-query'
import { apiClient } from '@/shared/api/client'
import type { AppError } from '@/shared/api/errors'
import type { TemplateDto } from './api-types'
import type { TemplateFieldType } from './enum'
import { toTemplate } from './mapper'
import type { Template } from './model'
import { templateKeys } from './query-keys'

export interface TemplateFieldInput {
  name: string
  label: string
  type: TemplateFieldType
  required: boolean
  options: string[]
}

export interface CreateTemplateInput {
  name: string
  description: string | null
  isDefault: boolean
  fields: TemplateFieldInput[]
}

export interface UpdateTemplateInput extends CreateTemplateInput {
  id: string
}

function toFieldDto(field: TemplateFieldInput): Record<string, unknown> {
  const dto: Record<string, unknown> = {
    name: field.name,
    label: field.label,
    type: field.type,
    required: field.required,
  }
  if (field.type === 'select') dto['options'] = field.options
  return dto
}

function toBody(input: CreateTemplateInput): Record<string, unknown> {
  return {
    name: input.name,
    description: input.description,
    is_default: input.isDefault,
    fields: input.fields.map(toFieldDto),
  }
}

export function useCreateTemplateMutation(): UseMutationResult<
  Template,
  AppError,
  CreateTemplateInput
> {
  const queryClient = useQueryClient()
  return useMutation<Template, AppError, CreateTemplateInput>({
    mutationFn: async (input) => {
      const dto = await apiClient.post<TemplateDto>('/templates', toBody(input))
      return toTemplate(dto)
    },
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: templateKeys.all })
    },
  })
}

export function useUpdateTemplateMutation(): UseMutationResult<
  Template,
  AppError,
  UpdateTemplateInput
> {
  const queryClient = useQueryClient()
  return useMutation<Template, AppError, UpdateTemplateInput>({
    mutationFn: async (input) => {
      const dto = await apiClient.put<TemplateDto>(
        `/templates/${encodeURIComponent(input.id)}`,
        toBody(input),
      )
      return toTemplate(dto)
    },
    onSuccess: (template) => {
      void queryClient.invalidateQueries({ queryKey: templateKeys.all })
      void queryClient.invalidateQueries({ queryKey: templateKeys.detail(template.id) })
    },
  })
}

export function useDeleteTemplateMutation(): UseMutationResult<undefined, AppError, string> {
  const queryClient = useQueryClient()
  return useMutation<undefined, AppError, string>({
    mutationFn: async (id) => {
      await apiClient.delete(`/templates/${encodeURIComponent(id)}`)
      return undefined
    },
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: templateKeys.all })
    },
  })
}
