import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient } from '@/shared/api/client'
import type { AppError } from '@/shared/api/errors'
import type { TemplateListResponseDto } from './api-types'
import { toTemplateList } from './mapper'
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
