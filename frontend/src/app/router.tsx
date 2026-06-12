import { createBrowserRouter, Navigate, RouterProvider } from 'react-router-dom'
import { AuditLogsPage } from '@/pages/audit-logs'
import { DashboardPage } from '@/pages/dashboard'
import { ExportPage } from '@/pages/export'
import { ReportDetailPage } from '@/pages/report-detail'
import { ReportSubmitPage } from '@/pages/report-submit'
import { ReportsPage } from '@/pages/reports'
import { SettingsPage } from '@/pages/settings'
import { TemplateCreatePage } from '@/pages/template-create'
import { TemplateEditPage } from '@/pages/template-edit'
import { TemplatesPage } from '@/pages/templates'
import { UserCreatePage } from '@/pages/user-create'
import { UserEditPage } from '@/pages/user-edit'
import { UsersPage } from '@/pages/users'
import { AdminShell } from '@/widgets/admin-shell'

const router = createBrowserRouter([
  {
    element: <AdminShell />,
    children: [
      { index: true, element: <DashboardPage /> },
      { path: 'reports', element: <ReportsPage /> },
      { path: 'reports/new', element: <ReportSubmitPage /> },
      { path: 'reports/:id', element: <ReportDetailPage /> },
      { path: 'templates', element: <TemplatesPage /> },
      { path: 'templates/new', element: <TemplateCreatePage /> },
      { path: 'templates/:id/edit', element: <TemplateEditPage /> },
      { path: 'users', element: <UsersPage /> },
      { path: 'users/new', element: <UserCreatePage /> },
      { path: 'users/:id/edit', element: <UserEditPage /> },
      { path: 'audit-logs', element: <AuditLogsPage /> },
      { path: 'export', element: <ExportPage /> },
      { path: 'settings', element: <SettingsPage /> },
      { path: '*', element: <Navigate to="/" replace /> },
    ],
  },
])

export function AppRouter() {
  return <RouterProvider router={router} />
}
