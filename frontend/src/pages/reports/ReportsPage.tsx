import { Link } from 'react-router-dom'
import { ListReports } from '@/features/list-reports'
import { useTranslation } from '@/shared/i18n'
import { Button, Stack, Text } from '@/shared/ui'

/**
 * Report list (admin index). Page chrome — sidebar, top bar, language toggle,
 * sign-out — is provided by the surrounding AdminShell; this renders content only.
 */
export function ReportsPage() {
  const { t } = useTranslation()

  return (
    <Stack gap="md" className="mx-auto w-full max-w-6xl">
      <div className="flex items-end justify-between gap-4">
        <Stack gap="sm">
          <Text variant="title" as="h2">
            {t('report.list.title')}
          </Text>
          <Text variant="subtitle">{t('report.list.subtitle')}</Text>
        </Stack>
        <Link to="/reports/new">
          <Button>{t('report.submit.newAction')}</Button>
        </Link>
      </div>
      <ListReports />
    </Stack>
  )
}
