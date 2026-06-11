import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient } from '@/shared/api/client'
import type { AppError } from '@/shared/api/errors'
import type { TemplateDto, TemplateListResponseDto } from './api-types'
import { toTemplate, toTemplateList } from './mapper'
import type { Template } from './model'
import { templateKeys } from './query-keys'

export function useTemplateListQuery(): UseQueryResult<Template[], AppError> {
  return useQuery<Template[], AppError>({
    queryKey: templateKeys.list(),
    queryFn: async () => {
      const dto = await apiClient.get<TemplateListResponseDto>('/templates')
      return toTemplateList(dto)
    },
  })
}

export function useTemplateQuery(
  id: string,
  options?: { enabled?: boolean },
): UseQueryResult<Template, AppError> {
  return useQuery<Template, AppError>({
    queryKey: templateKeys.detail(id),
    enabled: options?.enabled ?? true,
    queryFn: async () => {
      const dto = await apiClient.get<TemplateDto>(`/templates/${encodeURIComponent(id)}`)
      return toTemplate(dto)
    },
  })
}
