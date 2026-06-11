import { createBrowserRouter, Navigate, RouterProvider } from 'react-router-dom'
import { ReportsPage } from '@/pages/reports'

const router = createBrowserRouter([
  { path: '/', element: <ReportsPage /> },
  { path: '*', element: <Navigate to="/" replace /> },
])

export function AppRouter() {
  return <RouterProvider router={router} />
}
