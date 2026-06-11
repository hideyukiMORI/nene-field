import { Link, useNavigate } from 'react-router-dom'
import { SubmitReportForm } from '@/features/submit-report'
import { useTranslation } from '@/shared/i18n'
import { Text } from '@/shared/ui'

export function ReportSubmitPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()

  return (
    <div className="min-h-screen">
      <header className="flex items-center gap-4 border-b border-border bg-surface-raised px-6 py-3">
        <Link to="/" className="text-sm font-medium text-accent">
          ← {t('common.actions.back')}
        </Link>
        <Text variant="title" as="h1">
          {t('common.app.name')}
        </Text>
      </header>
      <SubmitReportForm
        onDone={(reportId) => {
          void navigate(`/reports/${reportId}`)
        }}
      />
    </div>
  )
}
