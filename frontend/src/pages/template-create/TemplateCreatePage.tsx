import { useSyncExternalStore } from 'react'
import { useNavigate } from 'react-router-dom'
import { canManageOrganization, getCurrentUser, subscribeCurrentUser } from '@/entities/auth'
import { TemplateForm, useTemplateEditor } from '@/features/manage-templates'
import { useTranslation } from '@/shared/i18n'
import { InlineAlert } from '@/shared/ui'

/**
 * Create template (admin). Pinned-toolbar (作業卓) editor: the AdminShell gives
 * this route a full-height, unpadded pane and TemplateForm owns the toolbar.
 */
export function TemplateCreatePage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const user = useSyncExternalStore(subscribeCurrentUser, getCurrentUser)
  const allowed = user !== null && canManageOrganization(user.role)
  const editor = useTemplateEditor(undefined, () => {
    void navigate('/templates')
  })

  if (!allowed) {
    return (
      <div className="p-6">
        <InlineAlert variant="error">{t('common.forbidden')}</InlineAlert>
      </div>
    )
  }

  return (
    <TemplateForm
      mode="create"
      onSave={editor.save}
      isPending={editor.isPending}
      errorKey={editor.errorKey}
    />
  )
}
