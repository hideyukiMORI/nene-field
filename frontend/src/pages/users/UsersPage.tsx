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
    return <InlineAlert variant="error">{t('common.forbidden')}</InlineAlert>
  }

  // Full-bleed: cancel the AdminShell <main> padding so the white header bar and
  // table area span the content edge-to-edge (design handoff layout).
  return (
    <div className="-m-6">
      <UserList />
    </div>
  )
}
