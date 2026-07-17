import {
  useOrganizationQuery,
  useUpdateOrganizationMutation,
  type Organization,
} from '@/entities/organization'
import type { MessageKey } from '@/shared/i18n'

export interface OrganizationSettingsValues {
  name: string
  aiSummaryEnabled: boolean
  notificationEmail: string | null
  webhookUrl: string | null
}

export interface OrganizationSettingsState {
  organization: Organization | undefined
  isLoading: boolean
  isError: boolean
  save: (values: OrganizationSettingsValues) => void
  isPending: boolean
  isSaved: boolean
  errorKey: MessageKey | null
}

export function useOrganizationSettings(orgId: string): OrganizationSettingsState {
  const detail = useOrganizationQuery(orgId, { enabled: orgId !== '' })
  const mutation = useUpdateOrganizationMutation()

  return {
    organization: detail.data,
    isLoading: detail.isLoading,
    isError: detail.isError,
    save: (values) => {
      mutation.mutate({ id: orgId, ...values })
    },
    isPending: mutation.isPending,
    isSaved: mutation.isSuccess,
    errorKey: mutation.error !== null ? 'settings.saveError' : null,
  }
}
