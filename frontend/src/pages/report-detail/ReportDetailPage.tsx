import { useSyncExternalStore } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { canApprove, getCurrentUser, subscribeCurrentUser } from '@/entities/auth'
import { ReviewActions } from '@/features/review-report'
import { ReportDetailView } from '@/features/view-report'
import { useTranslation } from '@/shared/i18n'

/** Report detail. Chrome from the surrounding shell; a back app bar + content. */
export function ReportDetailPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const params = useParams<{ id: string }>()
  const user = useSyncExternalStore(subscribeCurrentUser, getCurrentUser)
  const reportId = params.id ?? ''

  return (
    <div className="mx-auto w-full max-w-3xl">
      <header className="flex items-center gap-3 border-b border-border bg-surface-raised px-4 py-3">
        <button
          type="button"
          aria-label={t('common.actions.back')}
          onClick={() => {
            void navigate(-1)
          }}
          className="text-lg text-fg-muted"
        >
          ‹
        </button>
        <h1 className="text-base font-bold text-fg">{t('mobile.detail.back')}</h1>
      </header>
      <div className="p-4 sm:p-6">
        <ReportDetailView
          reportId={reportId}
          renderActions={(report) =>
            report.status === 'submitted' && user !== null && canApprove(user.role) ? (
              <ReviewActions reportId={report.id} />
            ) : null
          }
        />
      </div>
    </div>
  )
}
