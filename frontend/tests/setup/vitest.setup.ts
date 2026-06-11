import '@testing-library/jest-dom/vitest'
import { cleanup } from '@testing-library/react'
import { afterAll, afterEach, beforeAll } from 'vitest'
import { setAuthToken } from '@/shared/api/client'
import { server } from '@tests/msw/server'

beforeAll(() => {
  // Deterministic locale: jsdom reports navigator.language as en-US, so pin the
  // catalog to the ja master that the assertions are written against.
  localStorage.setItem('nene-field-locale', 'ja')
  server.listen({ onUnhandledRequest: 'error' })
})

afterEach(() => {
  cleanup()
  server.resetHandlers()
  setAuthToken(null)
})

afterAll(() => {
  server.close()
})
