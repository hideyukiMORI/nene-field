import { useSyncExternalStore } from 'react'
import { Link } from 'react-router-dom'
import { canManageOrganization, getCurrentUser, subscribeCurrentUser } from '@/entities/auth'
import { TemplateList } from '@/features/manage-templates'
import { useTranslation } from '@/shared/i18n'
import { InlineAlert, Stack, Text } from '@/shared/ui'

export function TemplatesPage() {
  const { t } = useTranslation()
  const user = useSyncExternalStore(subscribeCurrentUser, getCurrentUser)
  const allowed = user !== null && canManageOrganization(user.role)

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
      <main className="mx-auto w-full max-w-3xl p-6">
        {allowed ? (
          <Stack gap="md">
            <div className="flex items-end justify-between gap-4">
              <Stack gap="sm">
                <Text variant="title" as="h2">
                  {t('template.list.title')}
                </Text>
                <Text variant="subtitle">{t('template.list.subtitle')}</Text>
              </Stack>
              <Link
                to="/templates/new"
                className="inline-flex items-center border border-accent bg-accent px-3 py-2 text-sm font-semibold text-fg-inverse"
              >
                {t('template.list.newAction')}
              </Link>
            </div>
            <TemplateList />
          </Stack>
        ) : (
          <InlineAlert variant="error">{t('common.forbidden')}</InlineAlert>
        )}
      </main>
    </div>
  )
}
