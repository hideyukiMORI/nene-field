import {
  createNene2Transport,
  createSessionTokenStore,
  isNene2ClientError,
} from '@hideyukimori/nene2-client'
import { AppError } from './errors'

/**
 * The only module that calls `fetch` (via the fleet `@hideyukimori/nene2-client`
 * transport, adopted per the Stage2b migration guide
 * `nene2-js/docs-site/howto/migrate-product-client.md`).
 *
 * The bearer token lives in `sessionStorage` (fleet decision 2026-07-14,
 * `createSessionTokenStore`) rather than in memory: a signed-in session now
 * **survives a page reload** (previously in-memory only, lost on reload —
 * this is an intentional behavior change from the pre-migration client). It
 * is still cleared on sign-out and on a 401 of an authenticated request
 * (fail-closed → re-login).
 *
 * Every request is routed through the transport's single internal choke
 * point, which mirrors `Authorization` into `X-Authorization` on every verb
 * (JSON / blob) so hosting proxies that strip the standard header still see
 * the token.
 */
const tokenStore = createSessionTokenStore({ key: 'nene_field_token' })

let sessionExpired = false

const transport = createNene2Transport({
  baseUrl: '',
  tokenStore,
  // `createNene2Transport` resolves and binds `fetch` once, at call time. A
  // captured reference to `globalThis.fetch` would go stale under MSW, which
  // (re-)patches the global in `beforeAll` — after this module (and its
  // top-level `createNene2Transport` call) has already been imported. Look it
  // up per request instead, matching the pre-migration client's behavior of
  // calling the bare `fetch` identifier fresh on every call.
  fetch: (input, init) => globalThis.fetch(input, init),
  onUnauthorized: () => {
    sessionExpired = true
  },
})

export function setAuthToken(token: string | null): void {
  if (token === null) {
    tokenStore.clearToken()
    return
  }
  tokenStore.setToken(token)
  // A fresh sign-in clears any "session expired" notice on the login screen.
  sessionExpired = false
}

export function hasAuthToken(): boolean {
  return tokenStore.getToken() !== null
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
  return tokenStore.subscribe(listener)
}

/** Maps the transport's `Nene2ClientError` (RFC 9457 Problem Details) to the product `AppError`. */
function toAppError(error: unknown): AppError {
  if (isNene2ClientError(error)) {
    // status 0 = network failure / abort (never a real HTTP response).
    if (error.status === 0) return AppError.transport(error.message)
    return AppError.fromProblem(error.status, error.problem)
  }
  return AppError.transport(error instanceof Error ? error.message : 'Network request failed')
}

async function withAppError<T>(promise: Promise<T>): Promise<T> {
  try {
    return await promise
  } catch (error) {
    throw toAppError(error)
  }
}

type Json = Record<string, unknown>

export const apiClient = {
  get: <T>(path: string): Promise<T> => withAppError(transport.get<T>(path)),
  post: <T>(path: string, body?: Json): Promise<T> => withAppError(transport.post<T>(path, body)),
  put: <T>(path: string, body?: Json): Promise<T> => withAppError(transport.put<T>(path, body)),
  delete: <T>(path: string): Promise<T> => withAppError(transport.delete<T>(path)),
  getBlob: (path: string): Promise<Blob> =>
    withAppError(transport.getBlob(path).then((download) => download.blob)),
} as const
