import { createBrowserRouter, Navigate, RouterProvider } from 'react-router-dom'
import { ReportDetailPage } from '@/pages/report-detail'
import { ReportSubmitPage } from '@/pages/report-submit'
import { ReportsPage } from '@/pages/reports'

const router = createBrowserRouter([
  { path: '/', element: <ReportsPage /> },
  { path: '/reports/new', element: <ReportSubmitPage /> },
  { path: '/reports/:id', element: <ReportDetailPage /> },
  { path: '*', element: <Navigate to="/" replace /> },
])

export function AppRouter() {
  return <RouterProvider router={router} />
}
