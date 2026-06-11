import { useSyncExternalStore } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { canManageOrganization, getCurrentUser, subscribeCurrentUser } from '@/entities/auth'
import { TemplateForm, useTemplateEditor } from '@/features/manage-templates'
import { useTranslation } from '@/shared/i18n'
import { InlineAlert, LoadingState, Text } from '@/shared/ui'

export function TemplateEditPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const params = useParams<{ id: string }>()
  const user = useSyncExternalStore(subscribeCurrentUser, getCurrentUser)
  const allowed = user !== null && canManageOrganization(user.role)
  const editor = useTemplateEditor(params.id ?? '', () => {
    void navigate('/templates')
  })

  return (
    <div className="min-h-screen">
      <header className="flex items-center gap-4 border-b border-border bg-surface-raised px-6 py-3">
        <Link to="/templates" className="text-sm font-medium text-accent">
          ← {t('common.actions.back')}
        </Link>
        <Text variant="title" as="h1">
          {t('common.app.name')}
        </Text>
      </header>
      {!allowed ? (
        <main className="mx-auto w-full max-w-2xl p-6">
          <InlineAlert variant="error">{t('common.forbidden')}</InlineAlert>
        </main>
      ) : editor.isLoading ? (
        <LoadingState label={t('common.state.loading')} />
      ) : editor.initial === undefined ? (
        <main className="mx-auto w-full max-w-2xl p-6">
          <InlineAlert variant="error">{t('template.list.error')}</InlineAlert>
        </main>
      ) : (
        <TemplateForm
          mode="edit"
          initialTemplate={editor.initial}
          onSave={editor.save}
          isPending={editor.isPending}
          errorKey={editor.errorKey}
        />
      )}
    </div>
  )
}
