import { useSyncExternalStore } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { canManageOrganization, getCurrentUser, subscribeCurrentUser } from '@/entities/auth'
import { TemplateForm, useTemplateEditor } from '@/features/manage-templates'
import { useTranslation } from '@/shared/i18n'
import { InlineAlert, Text } from '@/shared/ui'

export function TemplateCreatePage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const user = useSyncExternalStore(subscribeCurrentUser, getCurrentUser)
  const allowed = user !== null && canManageOrganization(user.role)
  const editor = useTemplateEditor(undefined, () => {
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
      {allowed ? (
        <TemplateForm
          mode="create"
          onSave={editor.save}
          isPending={editor.isPending}
          errorKey={editor.errorKey}
        />
      ) : (
        <main className="mx-auto w-full max-w-2xl p-6">
          <InlineAlert variant="error">{t('common.forbidden')}</InlineAlert>
        </main>
      )}
    </div>
  )
}
