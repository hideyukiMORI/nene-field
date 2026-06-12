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
    return <InlineAlert variant="error">{t('common.forbidden')}</InlineAlert>
  }

  return (
    <div className="mx-auto w-full max-w-5xl">
      <ExportReportsForm />
    </div>
  )
}
