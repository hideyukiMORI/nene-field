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
    return (
      <div className="p-6">
        <InlineAlert variant="error">{t('common.forbidden')}</InlineAlert>
      </div>
    )
  }

  // Pinned-toolbar (作業卓) screen: full-height, unpadded pane from AdminShell;
  // AuditLog fills it and scrolls only its table body.
  return <AuditLog />
}
