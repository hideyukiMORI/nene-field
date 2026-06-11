import { useSyncExternalStore } from 'react'
import { Link, useParams } from 'react-router-dom'
import { canApprove, getCurrentUser, subscribeCurrentUser } from '@/entities/auth'
import { ReviewActions } from '@/features/review-report'
import { ReportDetailView } from '@/features/view-report'
import { useTranslation } from '@/shared/i18n'
import { Text } from '@/shared/ui'

export function ReportDetailPage() {
  const { t } = useTranslation()
  const params = useParams<{ id: string }>()
  const user = useSyncExternalStore(subscribeCurrentUser, getCurrentUser)
  const reportId = params.id ?? ''

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
      <main className="mx-auto w-full max-w-3xl p-6">
        <ReportDetailView
          reportId={reportId}
          renderActions={(report) =>
            report.status === 'submitted' && user !== null && canApprove(user.role) ? (
              <ReviewActions reportId={report.id} />
            ) : null
          }
        />
      </main>
    </div>
  )
}
