import { AdminShell } from '@/widgets/admin-shell'
import { MobileShell } from '@/widgets/mobile-shell'
import { useIsSubmitterSurface } from './use-submitter-surface'

/** Picks the shell by role: submitters get the mobile app, others the admin console. */
export function AppLayout() {
  return useIsSubmitterSurface() ? <MobileShell /> : <AdminShell />
}
