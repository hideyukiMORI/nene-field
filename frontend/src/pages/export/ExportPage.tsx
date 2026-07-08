import { useSyncExternalStore } from 'react'
import { canManageOrganization, getCurrentUser, subscribeCurrentUser } from '@/entities/auth'
import { ExportReportsForm } from '@/features/export-reports'
import { useTranslation } from '@/shared/i18n'
import { InlineAlert } from '@/shared/ui'

/** CSV export (admin). Chrome from AdminShell; content only. */
export function ExportPage() {
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

  // Setup-document (書類) screen: the AdminShell scrolls the whole pane; this
  // page owns the 30px document padding and ExportReportsForm centers its column.
  return (
    <div className="px-7.5 pt-7.5 pb-11">
      <ExportReportsForm />
    </div>
  )
}
