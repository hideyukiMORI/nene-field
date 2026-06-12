import { ListReports } from '@/features/list-reports'

/**
 * Report list (admin/approver). Page chrome — sidebar, top bar — is provided by
 * the AdminShell; the feature renders its own header row, filters, table, and
 * continuous-review drawer.
 */
export function ReportsPage() {
  // Full-bleed: cancel the AdminShell <main> padding so the white header bar and
  // table span edge-to-edge (design handoff screen type).
  return (
    <div className="-m-6">
      <ListReports />
    </div>
  )
}
