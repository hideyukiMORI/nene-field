import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient } from '@/shared/api/client'
import type { AppError } from '@/shared/api/errors'
import type { OrganizationDto } from './api-types'
import { toOrganization } from './mapper'
import type { Organization } from './model'
import { organizationKeys } from './query-keys'

export function useOrganizationQuery(
  id: string,
  options?: { enabled?: boolean },
): UseQueryResult<Organization, AppError> {
  return useQuery<Organization, AppError>({
    queryKey: organizationKeys.detail(id),
    enabled: options?.enabled ?? true,
    queryFn: async () => {
      const dto = await apiClient.get<OrganizationDto>(`/organizations/${encodeURIComponent(id)}`)
      return toOrganization(dto)
    },
  })
}
