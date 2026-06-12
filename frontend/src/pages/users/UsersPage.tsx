import { useSyncExternalStore } from 'react'
import { canManageOrganization, getCurrentUser, subscribeCurrentUser } from '@/entities/auth'
import { UserList } from '@/features/manage-users'
import { useTranslation } from '@/shared/i18n'
import { InlineAlert, Stack, Text } from '@/shared/ui'

/** User management (admin). Chrome is provided by AdminShell; content only. */
export function UsersPage() {
  const { t } = useTranslation()
  const user = useSyncExternalStore(subscribeCurrentUser, getCurrentUser)
  const allowed = user !== null && canManageOrganization(user.role)

  if (!allowed) {
    return <InlineAlert variant="error">{t('common.forbidden')}</InlineAlert>
  }

  return (
    <Stack gap="md" className="mx-auto w-full max-w-5xl">
      <Stack gap="sm">
        <Text variant="title" as="h2">
          {t('user.list.title')}
        </Text>
        <Text variant="subtitle">{t('user.list.subtitle')}</Text>
      </Stack>
      <UserList />
    </Stack>
  )
}
