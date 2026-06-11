import { createBrowserRouter, Navigate, RouterProvider } from 'react-router-dom'
import { ReportDetailPage } from '@/pages/report-detail'
import { ReportsPage } from '@/pages/reports'

const router = createBrowserRouter([
  { path: '/', element: <ReportsPage /> },
  { path: '/reports/:id', element: <ReportDetailPage /> },
  { path: '*', element: <Navigate to="/" replace /> },
])

export function AppRouter() {
  return <RouterProvider router={router} />
}
