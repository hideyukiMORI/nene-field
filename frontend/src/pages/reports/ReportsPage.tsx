import { ListReports } from '@/features/list-reports'

/**
 * Report list (admin/approver). Page chrome — sidebar, top bar — is provided by
 * the AdminShell; the feature renders its own header row, filters, table, and
 * continuous-review drawer.
 */
export function ReportsPage() {
  return (
    <div className="mx-auto w-full max-w-6xl">
      <ListReports />
    </div>
  )
}
