import { setAuthToken, wasSessionExpired } from '@/shared/api/client'
import type { AuthUser } from './model'

/** Whether the last sign-out was due to an expired/invalid token (vs. never signed in). */
export function sessionExpired(): boolean {
  return wasSessionExpired()
}

/**
 * In-memory current-user store (companion to the in-memory token in
 * shared/api/client). Lost on reload → fail-closed re-login. Exposed via
 * `useSyncExternalStore` for the app shell.
 */
let currentUser: AuthUser | null = null
const listeners = new Set<() => void>()

export function setCurrentUser(user: AuthUser | null): void {
  currentUser = user
  for (const listener of listeners) listener()
}

export function getCurrentUser(): AuthUser | null {
  return currentUser
}

export function subscribeCurrentUser(listener: () => void): () => void {
  listeners.add(listener)
  return () => {
    listeners.delete(listener)
  }
}

/** Clears the client session (token + user). The JWT is stateless server-side. */
export function signOut(): void {
  setCurrentUser(null)
  setAuthToken(null)
}
