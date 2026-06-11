import { useMutation, useQueryClient, type UseMutationResult } from '@tanstack/react-query'
import { apiClient } from '@/shared/api/client'
import type { AppError } from '@/shared/api/errors'
import type { OrganizationDto } from './api-types'
import { toOrganization } from './mapper'
import type { Organization } from './model'
import { organizationKeys } from './query-keys'

export interface UpdateOrganizationInput {
  id: string
  name: string
  aiSummaryEnabled: boolean
  /** `null` clears the field. */
  notificationEmail: string | null
  /** `null` clears the field. */
  webhookUrl: string | null
}

export function useUpdateOrganizationMutation(): UseMutationResult<
  Organization,
  AppError,
  UpdateOrganizationInput
> {
  const queryClient = useQueryClient()
  return useMutation<Organization, AppError, UpdateOrganizationInput>({
    mutationFn: async (input) => {
      const dto = await apiClient.put<OrganizationDto>(
        `/organizations/${encodeURIComponent(input.id)}`,
        {
          name: input.name,
          ai_summary_enabled: input.aiSummaryEnabled,
          notification_email: input.notificationEmail,
          webhook_url: input.webhookUrl,
        },
      )
      return toOrganization(dto)
    },
    onSuccess: (organization) => {
      void queryClient.invalidateQueries({ queryKey: organizationKeys.detail(organization.id) })
    },
  })
}
