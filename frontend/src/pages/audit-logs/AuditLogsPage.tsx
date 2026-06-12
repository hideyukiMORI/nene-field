import { useSyncExternalStore } from 'react'
import { canManageOrganization, getCurrentUser, subscribeCurrentUser } from '@/entities/auth'
import { AuditLog } from '@/features/view-audit-log'
import { useTranslation } from '@/shared/i18n'
import { InlineAlert, Stack, Text } from '@/shared/ui'

/** Audit log (admin). Chrome from AdminShell; content only. */
export function AuditLogsPage() {
  const { t } = useTranslation()
  const user = useSyncExternalStore(subscribeCurrentUser, getCurrentUser)
  const allowed = user !== null && canManageOrganization(user.role)

  if (!allowed) {
    return <InlineAlert variant="error">{t('common.forbidden')}</InlineAlert>
  }

  return (
    <Stack gap="md" className="mx-auto w-full max-w-6xl">
      <Stack gap="sm">
        <Text variant="title" as="h2">
          {t('audit.list.title')}
        </Text>
        <Text variant="subtitle">{t('audit.list.subtitle')}</Text>
      </Stack>
      <AuditLog />
    </Stack>
  )
}
