import { useSyncExternalStore } from 'react'
import { canManageOrganization, getCurrentUser, subscribeCurrentUser } from '@/entities/auth'
import { UserList } from '@/features/manage-users'
import { useTranslation } from '@/shared/i18n'
import { InlineAlert } from '@/shared/ui'

/** User management (admin). Chrome from AdminShell; UserList renders its own header. */
export function UsersPage() {
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
  // UserList fills it and scrolls only its table body.
  return <UserList />
}
