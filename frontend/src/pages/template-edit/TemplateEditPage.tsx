import { useSyncExternalStore } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { canManageOrganization, getCurrentUser, subscribeCurrentUser } from '@/entities/auth'
import { TemplateForm, useTemplateEditor } from '@/features/manage-templates'
import { useTranslation } from '@/shared/i18n'
import { InlineAlert, LoadingState } from '@/shared/ui'

/**
 * Edit template (admin). Pinned-toolbar (作業卓) editor: the AdminShell gives
 * this route a full-height, unpadded pane and TemplateForm owns the toolbar.
 */
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
    return (
      <div className="p-6">
        <InlineAlert variant="error">{t('common.forbidden')}</InlineAlert>
      </div>
    )
  }
  if (editor.isLoading) {
    return (
      <div className="p-6">
        <LoadingState label={t('common.state.loading')} />
      </div>
    )
  }
  if (editor.initial === undefined) {
    return (
      <div className="p-6">
        <InlineAlert variant="error">{t('template.list.error')}</InlineAlert>
      </div>
    )
  }

  return (
    <TemplateForm
      mode="edit"
      initialTemplate={editor.initial}
      onSave={editor.save}
      isPending={editor.isPending}
      errorKey={editor.errorKey}
    />
  )
}
