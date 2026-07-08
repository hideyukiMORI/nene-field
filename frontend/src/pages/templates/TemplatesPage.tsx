import { useSyncExternalStore } from 'react'
import { canManageOrganization, getCurrentUser, subscribeCurrentUser } from '@/entities/auth'
import { TemplateList } from '@/features/manage-templates'
import { useTranslation } from '@/shared/i18n'
import { InlineAlert } from '@/shared/ui'

/**
 * Template management (admin). Pinned-toolbar (作業卓) list screen — the AdminShell
 * gives this route a full-height, unpadded pane; TemplateList owns the toolbar.
 */
export function TemplatesPage() {
  const { t } = useTranslation()
  const user = useSyncExternalStore(subscribeCurrentUser, getCurrentUser)
  const allowed = user !== null && canManageOrganization(user.role)

  if (!allowed) {
    return (
      <div className="p-6">
        <InlineAlert variant="error">{t('common.forbidden')}</InlineAlert>
      </div>
    )
  }

  return <TemplateList />
}
