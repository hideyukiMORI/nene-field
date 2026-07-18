import { act, waitFor } from '@testing-library/react'
import { HttpResponse, http } from 'msw'
import { afterEach, describe, expect, it } from 'vitest'
import { renderHookWithProviders } from '@tests/render/render-with-providers'
import { server } from '@tests/msw/server'
import { getCurrentUser, signOut } from '@/entities/auth'
import { useSignIn } from './use-sign-in'

const BASE = 'https://nene-field.dev/problems'

/**
 * T1 (#112): the sign-in model is the integration point where a successful
 * login establishes the in-memory session (token + current user) and where the
 * API's 401 is reduced to a user-facing message key. These tests pin both
 * paths through the real mutation + MSW, not by mocking the entity layer.
 */
describe('useSignIn', () => {
  afterEach(() => {
    signOut()
  })

  it('establishes the current user on successful sign-in and keeps errorKey null', async () => {
    const { result } = renderHookWithProviders(() => useSignIn())

    act(() => {
      result.current.signIn({ email: 'worker@example.com', password: 'ok' })
    })

    await waitFor(() => {
      expect(getCurrentUser()?.email).toBe('worker@example.com')
    })
    expect(result.current.errorKey).toBeNull()
    expect(result.current.isPending).toBe(false)
  })

  it('maps a 401 to auth.login.invalid and does not establish a session', async () => {
    const { result } = renderHookWithProviders(() => useSignIn())

    act(() => {
      result.current.signIn({ email: 'worker@example.com', password: 'wrong' })
    })

    await waitFor(() => {
      expect(result.current.errorKey).toBe('auth.login.invalid')
    })
    expect(getCurrentUser()).toBeNull()
  })

  it('maps any non-401 failure to error.generic', async () => {
    server.use(
      http.post('/auth/login', () =>
        HttpResponse.json(
          { type: `${BASE}/internal-error`, title: 'Internal Server Error' },
          { status: 500 },
        ),
      ),
    )
    const { result } = renderHookWithProviders(() => useSignIn())

    act(() => {
      result.current.signIn({ email: 'worker@example.com', password: 'ok' })
    })

    await waitFor(() => {
      expect(result.current.errorKey).toBe('error.generic')
    })
    expect(getCurrentUser()).toBeNull()
  })

  it('recovers after a failed attempt: retry clears errorKey and signs in', async () => {
    const { result } = renderHookWithProviders(() => useSignIn())

    act(() => {
      result.current.signIn({ email: 'worker@example.com', password: 'wrong' })
    })
    await waitFor(() => {
      expect(result.current.errorKey).toBe('auth.login.invalid')
    })

    act(() => {
      result.current.signIn({ email: 'worker@example.com', password: 'ok' })
    })
    await waitFor(() => {
      expect(getCurrentUser()?.email).toBe('worker@example.com')
    })
    expect(result.current.errorKey).toBeNull()
  })
})
