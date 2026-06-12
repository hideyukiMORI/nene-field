import { useNavigate } from 'react-router-dom'
import { SubmitReportForm } from '@/features/submit-report'

/** New report (mobile). The form renders its own app bar; full-height content. */
export function ReportSubmitPage() {
  const navigate = useNavigate()

  return (
    <div className="h-full">
      <SubmitReportForm
        onDone={(reportId) => {
          void navigate(`/reports/${reportId}`)
        }}
      />
    </div>
  )
}
