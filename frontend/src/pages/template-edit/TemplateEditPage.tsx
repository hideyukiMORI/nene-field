import { useSyncExternalStore } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { canManageOrganization, getCurrentUser, subscribeCurrentUser } from '@/entities/auth'
import { TemplateForm, useTemplateEditor } from '@/features/manage-templates'
import { useTranslation } from '@/shared/i18n'
import { InlineAlert, LoadingState, Stack, Text } from '@/shared/ui'

/** Edit template (admin). Chrome from AdminShell; content only. */
export function TemplateEditPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const params = useParams<{ id: string }>()
  const user = useSyncExternalStore(subscribeCurrentUser, getCurrentUser)
  const allowed = user !== null && canManageOrganization(user.role)
  const editor = useTemplateEditor(params.id ?? '', () => {
    void navigate('/templates')
  })

  if (!allowed) {
    return <InlineAlert variant="error">{t('common.forbidden')}</InlineAlert>
  }
  if (editor.isLoading) {
    return <LoadingState label={t('common.state.loading')} />
  }
  if (editor.initial === undefined) {
    return <InlineAlert variant="error">{t('template.list.error')}</InlineAlert>
  }

  return (
    <Stack gap="md" className="mx-auto w-full max-w-5xl">
      <Stack gap="sm">
        <Link to="/templates" className="text-sm font-medium text-accent">
          ← {t('common.actions.back')}
        </Link>
        <Text variant="title" as="h2">
          {t('template.form.editTitle')}
        </Text>
      </Stack>
      <TemplateForm
        mode="edit"
        initialTemplate={editor.initial}
        onSave={editor.save}
        isPending={editor.isPending}
        errorKey={editor.errorKey}
      />
    </Stack>
  )
}
