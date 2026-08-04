import { AdminShell } from './shells/admin-shell'
import { MobileShell } from './shells/mobile-shell'
import { useIsSubmitterSurface } from './use-submitter-surface'

/** Picks the shell by role: submitters get the mobile app, others the admin console. */
export function AppLayout() {
  return useIsSubmitterSurface() ? <MobileShell /> : <AdminShell />
}
