import { AppError } from './errors'

/**
 * The only module that calls `fetch`. Transport only — no domain logic.
 *
 * The bearer token lives in memory (frontend-standards: in-memory by default;
 * localStorage/cookie session needs an ADR). The auth flow sets it via
 * `setAuthToken`; it is lost on reload (fail-closed → re-login).
 */
let authToken: string | null = null
let sessionExpired = false
const authListeners = new Set<() => void>()

export function setAuthToken(token: string | null): void {
  authToken = token
  // A fresh sign-in clears any "session expired" notice on the login screen.
  if (token !== null) sessionExpired = false
  for (const listener of authListeners) listener()
}

export function hasAuthToken(): boolean {
  return authToken !== null
}

/**
 * True when the session ended because a request came back 401 (expired/invalid
 * token), as opposed to never having signed in. Cleared on the next sign-in.
 */
export function wasSessionExpired(): boolean {
  return sessionExpired
}

/** Subscribe to token / session changes (for `useSyncExternalStore`). */
export function subscribeAuthChange(listener: () => void): () => void {
  authListeners.add(listener)
  return () => {
    authListeners.delete(listener)
  }
}

/**
 * A 401 means the session expired or the token is invalid. Clear it so the
 * fail-closed auth shell shows the login screen, and flag the expiry so the
 * login form can say why. No-op when not signed in (a failed login attempt's
 * 401 is a credentials error to surface on the form instead).
 */
function handleUnauthorized(status: number): void {
  if (status === 401 && authToken !== null) {
    sessionExpired = true
    setAuthToken(null)
  }
}

type Json = Record<string, unknown>

async function request<T>(method: string, path: string, body?: Json): Promise<T> {
  const headers: Record<string, string> = { Accept: 'application/json' }
  if (body !== undefined) headers['Content-Type'] = 'application/json'
  if (authToken !== null) headers['Authorization'] = `Bearer ${authToken}`

  // Built via a mutable RequestInit (rather than a `body: cond ? x : undefined`
  // literal) so the `body` key is omitted entirely when there is none —
  // exactOptionalPropertyTypes forbids assigning `undefined` to `RequestInit.body`
  // (`BodyInit | null`, no `undefined` in the union), and omitting the key is
  // behaviorally identical to passing `body: undefined` for `fetch`.
  const init: RequestInit = { method, headers }
  if (body !== undefined) init.body = JSON.stringify(body)

  let response: Response
  try {
    response = await fetch(path, init)
  } catch {
    throw AppError.transport('Network request failed')
  }

  if (response.status === 204) {
    return undefined as T
  }

  const text = await response.text()
  const parsed: unknown = text === '' ? null : safeJsonParse(text)

  if (!response.ok) {
    handleUnauthorized(response.status)
    throw AppError.fromProblem(response.status, parsed)
  }

  return parsed as T
}

function safeJsonParse(text: string): unknown {
  try {
    return JSON.parse(text)
  } catch {
    return null
  }
}

/** Fetches a binary resource (CSV / attachment) as a Blob. Sends the bearer token. */
async function requestBlob(path: string): Promise<Blob> {
  const headers: Record<string, string> = {}
  if (authToken !== null) headers['Authorization'] = `Bearer ${authToken}`

  let response: Response
  try {
    response = await fetch(path, { method: 'GET', headers })
  } catch {
    throw AppError.transport('Network request failed')
  }

  if (!response.ok) {
    handleUnauthorized(response.status)
    const text = await response.text()
    throw AppError.fromProblem(response.status, text === '' ? null : safeJsonParse(text))
  }

  return response.blob()
}

export const apiClient = {
  get: <T>(path: string): Promise<T> => request<T>('GET', path),
  post: <T>(path: string, body?: Json): Promise<T> => request<T>('POST', path, body),
  put: <T>(path: string, body?: Json): Promise<T> => request<T>('PUT', path, body),
  delete: <T>(path: string): Promise<T> => request<T>('DELETE', path),
  getBlob: (path: string): Promise<Blob> => requestBlob(path),
} as const
