import { useSyncExternalStore } from 'react'
import { canManageOrganization, getCurrentUser, subscribeCurrentUser } from '@/entities/auth'
import { OrganizationSettingsForm, useOrganizationSettings } from '@/features/edit-organization'
import { useTranslation } from '@/shared/i18n'
import { InlineAlert, LoadingState } from '@/shared/ui'

/** Organization settings (admin). Chrome from AdminShell; content only. */
export function SettingsPage() {
  const { t } = useTranslation()
  const user = useSyncExternalStore(subscribeCurrentUser, getCurrentUser)
  const allowed = user !== null && canManageOrganization(user.role)
  const settings = useOrganizationSettings(user?.organizationId ?? '')

  if (!allowed) {
    return (
      <div className="p-6">
        <InlineAlert variant="error">{t('common.forbidden')}</InlineAlert>
      </div>
    )
  }

  // Setup-document (書類) screen: the AdminShell scrolls the whole pane; this page
  // owns the 30px document padding and the form centers its 760px column.
  return (
    <div className="px-7.5 pt-7.5 pb-11">
      {settings.isLoading ? (
        <LoadingState label={t('common.state.loading')} />
      ) : settings.organization === undefined ? (
        <InlineAlert variant="error">{t('settings.error')}</InlineAlert>
      ) : (
        <OrganizationSettingsForm
          organization={settings.organization}
          onSave={settings.save}
          isPending={settings.isPending}
          isSaved={settings.isSaved}
          errorKey={settings.errorKey}
        />
      )}
    </div>
  )
}
