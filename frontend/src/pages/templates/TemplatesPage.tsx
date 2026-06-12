import { useSyncExternalStore } from 'react'
import { Link } from 'react-router-dom'
import { canManageOrganization, getCurrentUser, subscribeCurrentUser } from '@/entities/auth'
import { TemplateList } from '@/features/manage-templates'
import { useTranslation } from '@/shared/i18n'
import { Button, InlineAlert, Stack, Text } from '@/shared/ui'

/** Template management (admin). Chrome from AdminShell; content only. */
export function TemplatesPage() {
  const { t } = useTranslation()
  const user = useSyncExternalStore(subscribeCurrentUser, getCurrentUser)
  const allowed = user !== null && canManageOrganization(user.role)

  if (!allowed) {
    return <InlineAlert variant="error">{t('common.forbidden')}</InlineAlert>
  }

  return (
    <Stack gap="md" className="mx-auto w-full max-w-5xl">
      <div className="flex items-end justify-between gap-4">
        <Stack gap="sm">
          <Text variant="title" as="h2">
            {t('template.list.title')}
          </Text>
          <Text variant="subtitle">{t('template.list.subtitle')}</Text>
        </Stack>
        <Link to="/templates/new">
          <Button>＋ {t('template.list.newAction')}</Button>
        </Link>
      </div>
      <TemplateList />
    </Stack>
  )
}
