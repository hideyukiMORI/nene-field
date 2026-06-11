import { useSyncExternalStore } from 'react'
import { Link } from 'react-router-dom'
import { canManageOrganization, getCurrentUser, subscribeCurrentUser } from '@/entities/auth'
import { ExportReportsForm } from '@/features/export-reports'
import { useTranslation } from '@/shared/i18n'
import { InlineAlert, Text } from '@/shared/ui'

export function ExportPage() {
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
      <main className="mx-auto w-full max-w-2xl p-6">
        {allowed ? (
          <ExportReportsForm />
        ) : (
          <InlineAlert variant="error">{t('common.forbidden')}</InlineAlert>
        )}
      </main>
    </div>
  )
}
