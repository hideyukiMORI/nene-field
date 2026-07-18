import { describe, expect, it } from 'vitest'
import { AppError } from './errors'

/**
 * T1 (#109): AppError is the single error type crossing the client boundary —
 * every feature maps `slug` / `status` from it. These tests pin the Problem
 * Details normalization (fromProblem) and the classification getters so a
 * malformed or hostile response body can never produce a malformed error.
 */
describe('AppError.fromProblem', () => {
  it('reduces the Problem Details type URL to its last path segment', () => {
    const err = AppError.fromProblem(422, {
      type: 'https://nene-field.dev/problems/validation-failed',
      title: 'Validation failed',
    })
    expect(err.slug).toBe('validation-failed')
    expect(err.status).toBe(422)
    expect(err.message).toBe('Validation failed')
  })

  it('ignores trailing slashes on the type URL', () => {
    const err = AppError.fromProblem(404, {
      type: 'https://nene-field.dev/problems/not-found///',
      title: 'Not found',
    })
    expect(err.slug).toBe('not-found')
  })

  it('falls back to the "error" slug when type is missing, empty, or not a string', () => {
    expect(AppError.fromProblem(500, {}).slug).toBe('error')
    expect(AppError.fromProblem(500, { type: '' }).slug).toBe('error')
    expect(AppError.fromProblem(500, { type: 42 }).slug).toBe('error')
    expect(AppError.fromProblem(500, { type: '///' }).slug).toBe('error')
  })

  it('falls back to an HTTP status title when title is missing or not a string', () => {
    expect(AppError.fromProblem(503, {}).message).toBe('HTTP 503')
    expect(AppError.fromProblem(503, { title: { nested: true } }).message).toBe('HTTP 503')
  })

  it('drops detail unless it is a string', () => {
    expect(AppError.fromProblem(400, { detail: 'boom' }).detail).toBe('boom')
    expect(AppError.fromProblem(400, { detail: 42 }).detail).toBeUndefined()
    expect(AppError.fromProblem(400, {}).detail).toBeUndefined()
  })

  it('passes field errors through and normalizes non-arrays to empty', () => {
    const fieldErrors = [{ field: 'title', code: 'required' }]
    expect(AppError.fromProblem(422, { errors: fieldErrors }).fieldErrors).toEqual(fieldErrors)
    expect(AppError.fromProblem(422, { errors: 'nope' }).fieldErrors).toEqual([])
    expect(AppError.fromProblem(422, {}).fieldErrors).toEqual([])
  })

  it('tolerates a body that is not an object', () => {
    for (const body of [null, undefined, 'oops', 42]) {
      const err = AppError.fromProblem(500, body)
      expect(err.slug).toBe('error')
      expect(err.message).toBe('HTTP 500')
      expect(err.fieldErrors).toEqual([])
    }
  })
})

describe('AppError.transport', () => {
  it('builds a status-0 network-error with the given message', () => {
    const err = AppError.transport('fetch failed')
    expect(err.status).toBe(0)
    expect(err.slug).toBe('network-error')
    expect(err.message).toBe('fetch failed')
  })
})

describe('AppError classification getters', () => {
  it('marks 5xx and 408/429 as retryable, other 4xx as not', () => {
    expect(AppError.fromProblem(500, {}).isRetryable).toBe(true)
    expect(AppError.fromProblem(503, {}).isRetryable).toBe(true)
    expect(AppError.fromProblem(408, {}).isRetryable).toBe(true)
    expect(AppError.fromProblem(429, {}).isRetryable).toBe(true)
    expect(AppError.fromProblem(400, {}).isRetryable).toBe(false)
    expect(AppError.fromProblem(404, {}).isRetryable).toBe(false)
    // Pins current behavior: transport failures (status 0) are NOT retryable.
    expect(AppError.transport('fetch failed').isRetryable).toBe(false)
  })

  it('maps 401 to isUnauthorized and 403 to isForbidden, exclusively', () => {
    const unauthorized = AppError.fromProblem(401, {})
    expect(unauthorized.isUnauthorized).toBe(true)
    expect(unauthorized.isForbidden).toBe(false)
    const forbidden = AppError.fromProblem(403, {})
    expect(forbidden.isForbidden).toBe(true)
    expect(forbidden.isUnauthorized).toBe(false)
  })

  it('is a real Error with a stable name', () => {
    const err = AppError.fromProblem(500, {})
    expect(err).toBeInstanceOf(Error)
    expect(err.name).toBe('AppError')
  })
})
