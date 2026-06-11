import { Link } from 'react-router-dom'
import { getCurrentUser } from '@/entities/auth'
import type { UserRole } from '@/entities/user'
import { useTranslation } from '@/shared/i18n'
import type { MessageKey } from '@/shared/i18n'
import { Badge, Button, EmptyState, ErrorState, LoadingState } from '@/shared/ui'
import { useUserList } from '../hooks/use-user-list'

const roleLabelKey: Record<UserRole, MessageKey> = {
  submitter: 'user.role.submitter',
  approver: 'user.role.approver',
  admin: 'user.role.admin',
  superadmin: 'user.role.superadmin',
}

const roleTone = {
  submitter: 'neutral',
  approver: 'success',
  admin: 'info',
  superadmin: 'warn',
} as const

export function UserList() {
  const { t } = useTranslation()
  const { users, isLoading, isError, refetch, remove, isDeleting } = useUserList()
  const currentUserId = getCurrentUser()?.id ?? null

  if (isLoading) {
    return <LoadingState label={t('common.state.loading')} />
  }
  if (isError) {
    return (
      <ErrorState
        message={t('user.list.error')}
        retryLabel={t('common.actions.retry')}
        onRetry={refetch}
      />
    )
  }
  if (users.length === 0) {
    return <EmptyState message={t('user.list.empty')} />
  }

  return (
    <div className="overflow-x-auto border border-border bg-surface-raised">
      <table className="w-full text-sm">
        <thead>
          <tr className="border-b border-border-strong text-left text-fg-muted">
            <th scope="col" className="px-3 py-2 font-semibold">
              {t('user.list.colName')}
            </th>
            <th scope="col" className="px-3 py-2 font-semibold">
              {t('user.list.colEmail')}
            </th>
            <th scope="col" className="px-3 py-2 font-semibold">
              {t('user.list.colRole')}
            </th>
            <th scope="col" className="px-3 py-2 font-semibold">
              {t('user.list.colStatus')}
            </th>
            <th scope="col" className="px-3 py-2" />
          </tr>
        </thead>
        <tbody>
          {users.map((user) => (
            <tr key={user.id} className="border-b border-border">
              <td className="px-3 py-2 text-fg">{user.name}</td>
              <td className="px-3 py-2 text-fg-muted">{user.email}</td>
              <td className="px-3 py-2">
                <Badge tone={roleTone[user.role]}>{t(roleLabelKey[user.role])}</Badge>
              </td>
              <td className="px-3 py-2">
                <Badge tone={user.isActive ? 'success' : 'neutral'}>
                  {t(user.isActive ? 'user.status.active' : 'user.status.inactive')}
                </Badge>
              </td>
              <td className="px-3 py-2">
                <div className="flex justify-end gap-2">
                  <Link
                    to={`/users/${user.id}/edit`}
                    className="inline-flex items-center border border-border-strong px-3 py-1 text-sm font-medium text-fg"
                  >
                    {t('common.actions.edit')}
                  </Link>
                  {user.id !== currentUserId && (
                    <Button
                      variant="danger"
                      disabled={isDeleting}
                      onClick={() => {
                        if (window.confirm(t('user.delete.confirm'))) {
                          remove(user.id)
                        }
                      }}
                    >
                      {t('common.actions.delete')}
                    </Button>
                  )}
                </div>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  )
}
