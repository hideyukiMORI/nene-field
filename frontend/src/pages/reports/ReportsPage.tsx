import { ListReports } from '@/features/list-reports'

/**
 * Report list (admin/approver). Page chrome — sidebar, top bar — is provided by
 * the AdminShell; the feature renders its own header row, filters, table, and
 * continuous-review drawer.
 */
export function ReportsPage() {
  // Pinned-toolbar (作業卓) screen: the AdminShell gives this route a full-height,
  // unpadded pane; ListReports fills it and scrolls only its table body.
  return <ListReports />
}
