import { useSyncExternalStore } from 'react'
import { canManageOrganization, getCurrentUser, subscribeCurrentUser } from '@/entities/auth'
import { AuditLog } from '@/features/view-audit-log'
import { useTranslation } from '@/shared/i18n'
import { InlineAlert } from '@/shared/ui'

/** Audit log (admin). Full-bleed white-header screen; AuditLog renders its own header. */
export function AuditLogsPage() {
  const { t } = useTranslation()
  const user = useSyncExternalStore(subscribeCurrentUser, getCurrentUser)
  const allowed = user !== null && canManageOrganization(user.role)

  if (!allowed) {
    return <InlineAlert variant="error">{t('common.forbidden')}</InlineAlert>
  }

  return (
    <div className="-m-6">
      <AuditLog />
    </div>
  )
}
