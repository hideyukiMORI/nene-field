import { useSyncExternalStore } from 'react'
import { Link } from 'react-router-dom'
import { canManageOrganization, getCurrentUser, subscribeCurrentUser } from '@/entities/auth'
import { OrganizationSettingsForm, useOrganizationSettings } from '@/features/edit-organization'
import { useTranslation } from '@/shared/i18n'
import { InlineAlert, LoadingState, Text } from '@/shared/ui'

export function SettingsPage() {
  const { t } = useTranslation()
  const user = useSyncExternalStore(subscribeCurrentUser, getCurrentUser)
  const allowed = user !== null && canManageOrganization(user.role)
  const settings = useOrganizationSettings(user?.organizationId ?? '')

  return (
    <div className="min-h-screen">
      <header className="flex items-center gap-4 border-b border-border bg-surface-raised px-6 py-3">
        <Link to="/" className="text-sm font-medium text-accent">
          ← {t('common.actions.back')}
        </Link>
        <Text variant="title" as="h1">
          {t('common.app.name')}
        </Text>
      </header>
      <main className="mx-auto w-full max-w-2xl p-6">
        {!allowed ? (
          <InlineAlert variant="error">{t('common.forbidden')}</InlineAlert>
        ) : settings.isLoading ? (
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
      </main>
    </div>
  )
}
