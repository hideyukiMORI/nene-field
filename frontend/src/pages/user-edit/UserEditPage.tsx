import { useSyncExternalStore } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { canManageOrganization, getCurrentUser, subscribeCurrentUser } from '@/entities/auth'
import { UserEditForm, useEditUser } from '@/features/manage-users'
import { useTranslation } from '@/shared/i18n'
import { InlineAlert, LoadingState, Text } from '@/shared/ui'

export function UserEditPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const params = useParams<{ id: string }>()
  const current = useSyncExternalStore(subscribeCurrentUser, getCurrentUser)
  const allowed = current !== null && canManageOrganization(current.role)
  const editor = useEditUser(params.id ?? '', () => {
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
      {!allowed ? (
        <main className="mx-auto w-full max-w-md p-6">
          <InlineAlert variant="error">{t('common.forbidden')}</InlineAlert>
        </main>
      ) : editor.isLoading ? (
        <LoadingState label={t('common.state.loading')} />
      ) : editor.initial === undefined ? (
        <main className="mx-auto w-full max-w-md p-6">
          <InlineAlert variant="error">{t('user.list.error')}</InlineAlert>
        </main>
      ) : (
        <UserEditForm
          user={editor.initial}
          onSubmit={editor.save}
          isPending={editor.isPending}
          errorKey={editor.errorKey}
        />
      )}
    </div>
  )
}
