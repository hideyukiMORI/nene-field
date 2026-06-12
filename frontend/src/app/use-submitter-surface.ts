import { useSyncExternalStore } from 'react'
import { getCurrentUser, subscribeCurrentUser } from '@/entities/auth'

/** Whether the current user uses the submitter mobile surface (vs. the admin PC shell). */
export function useIsSubmitterSurface(): boolean {
  const user = useSyncExternalStore(subscribeCurrentUser, getCurrentUser)
  return user !== null && user.role === 'submitter'
}
