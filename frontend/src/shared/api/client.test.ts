import { HttpResponse, http } from 'msw'
import { describe, expect, it } from 'vitest'
import { server } from '@tests/msw/server'
import { apiClient, setAuthToken } from './client'

/**
 * Stage2b (#95): the shared client now routes every request through the
 * `@hideyukimori/nene2-client` transport, whose single internal choke point
 * mirrors `Authorization` into `X-Authorization` on every verb — JSON and
 * blob alike. These tests pin that structural guarantee at the product level
 * (not just re-testing the package itself).
 */
describe('apiClient auth headers', () => {
  it('sends both Authorization and X-Authorization on a JSON GET', async () => {
    let seen: { authorization: string | null; xAuthorization: string | null } | undefined
    server.use(
      http.get('/reports', ({ request }) => {
        seen = {
          authorization: request.headers.get('authorization'),
          xAuthorization: request.headers.get('x-authorization'),
        }
        return HttpResponse.json({ items: [], limit: 20, offset: 0, total: 0 })
      }),
    )

    setAuthToken('test-jwt')
    await apiClient.get('/reports')

    expect(seen).toEqual({
      authorization: 'Bearer test-jwt',
      xAuthorization: 'Bearer test-jwt',
    })
  })

  it('sends both Authorization and X-Authorization on a blob download', async () => {
    let seen: { authorization: string | null; xAuthorization: string | null } | undefined
    server.use(
      http.get('/export/csv', ({ request }) => {
        seen = {
          authorization: request.headers.get('authorization'),
          xAuthorization: request.headers.get('x-authorization'),
        }
        return HttpResponse.text('id,name\n')
      }),
    )

    setAuthToken('test-jwt')
    await apiClient.getBlob('/export/csv')

    expect(seen).toEqual({
      authorization: 'Bearer test-jwt',
      xAuthorization: 'Bearer test-jwt',
    })
  })
})
