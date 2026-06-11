import { useSyncExternalStore } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { canManageOrganization, getCurrentUser, subscribeCurrentUser } from '@/entities/auth'
import { UserCreateForm, useCreateUser } from '@/features/manage-users'
import { useTranslation } from '@/shared/i18n'
import { InlineAlert, Text } from '@/shared/ui'

export function UserCreatePage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const current = useSyncExternalStore(subscribeCurrentUser, getCurrentUser)
  const allowed = current !== null && canManageOrganization(current.role)
  const { create, isPending, errorKey } = useCreateUser(() => {
    void navigate('/users')
  })

  return (
    <div className="min-h-screen">
      <header className="flex items-center gap-4 border-b border-border bg-surface-raised px-6 py-3">
        <Link to="/users" className="text-sm font-medium text-accent">
          ← {t('common.actions.back')}
        </Link>
        <Text variant="title" as="h1">
          {t('common.app.name')}
        </Text>
      </header>
      {allowed ? (
        <UserCreateForm onSubmit={create} isPending={isPending} errorKey={errorKey} />
      ) : (
        <main className="mx-auto w-full max-w-md p-6">
          <InlineAlert variant="error">{t('common.forbidden')}</InlineAlert>
        </main>
      )}
    </div>
  )
}
