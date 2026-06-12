import { createBrowserRouter, Navigate, RouterProvider } from 'react-router-dom'
import { AccountPage } from '@/pages/account'
import { AuditLogsPage } from '@/pages/audit-logs'
import { DashboardPage } from '@/pages/dashboard'
import { ExportPage } from '@/pages/export'
import { MobileHomePage } from '@/pages/mobile-home'
import { MobileNotificationsPage } from '@/pages/mobile-notifications'
import { MobileReportsPage } from '@/pages/mobile-reports'
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
import { AppLayout } from './app-layout'
import { useIsSubmitterSurface } from './use-submitter-surface'

/** '/' and '/reports' render different content for submitters vs. managers. */
function HomeRoute() {
  return useIsSubmitterSurface() ? <MobileHomePage /> : <DashboardPage />
}
function ReportsRoute() {
  return useIsSubmitterSurface() ? <MobileReportsPage /> : <ReportsPage />
}

const router = createBrowserRouter([
  {
    element: <AppLayout />,
    children: [
      { index: true, element: <HomeRoute /> },
      { path: 'reports', element: <ReportsRoute /> },
      { path: 'reports/new', element: <ReportSubmitPage /> },
      { path: 'reports/:id', element: <ReportDetailPage /> },
      { path: 'account', element: <AccountPage /> },
      { path: 'notifications', element: <MobileNotificationsPage /> },
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
