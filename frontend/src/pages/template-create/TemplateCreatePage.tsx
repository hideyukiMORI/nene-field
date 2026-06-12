import { useSyncExternalStore } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { canManageOrganization, getCurrentUser, subscribeCurrentUser } from '@/entities/auth'
import { TemplateForm, useTemplateEditor } from '@/features/manage-templates'
import { useTranslation } from '@/shared/i18n'
import { InlineAlert, Stack, Text } from '@/shared/ui'

/** Create template (admin). Chrome from AdminShell; content only. */
export function TemplateCreatePage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const user = useSyncExternalStore(subscribeCurrentUser, getCurrentUser)
  const allowed = user !== null && canManageOrganization(user.role)
  const editor = useTemplateEditor(undefined, () => {
    void navigate('/templates')
  })

  if (!allowed) {
    return <InlineAlert variant="error">{t('common.forbidden')}</InlineAlert>
  }

  return (
    <Stack gap="md" className="mx-auto w-full max-w-5xl">
      <Stack gap="sm">
        <Link to="/templates" className="text-sm font-medium text-accent">
          ← {t('common.actions.back')}
        </Link>
        <Text variant="title" as="h2">
          {t('template.form.createTitle')}
        </Text>
      </Stack>
      <TemplateForm
        mode="create"
        onSave={editor.save}
        isPending={editor.isPending}
        errorKey={editor.errorKey}
      />
    </Stack>
  )
}
