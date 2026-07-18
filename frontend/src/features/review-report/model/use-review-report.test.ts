import { act, waitFor } from '@testing-library/react'
import { HttpResponse, http } from 'msw'
import { describe, expect, it, vi } from 'vitest'
import { renderHookWithProviders } from '@tests/render/render-with-providers'
import { server } from '@tests/msw/server'
import { reportDetail } from '@tests/msw/handlers'
import { useReviewReport } from './use-review-report'

const BASE = 'https://nene-field.dev/problems'

/**
 * T1 (#114): the review model owns the approve/reject request contract —
 * approve omits an absent/empty comment entirely, reject always sends one.
 * These tests pin the outgoing bodies (captured at the MSW boundary), the
 * onDone callback, and the shared error key.
 */
describe('useReviewReport', () => {
  it('approve posts to /approve without a comment key when comment is absent or empty', async () => {
    const bodies: unknown[] = []
    server.use(
      http.post('/reports/:id/approve', async ({ request }) => {
        bodies.push(await request.json())
        return HttpResponse.json(reportDetail('approved'))
      }),
    )
    const onDone = vi.fn()
    const { result } = renderHookWithProviders(() => useReviewReport('r-1'))

    act(() => {
      result.current.approve(undefined, onDone)
    })
    await waitFor(() => {
      expect(onDone).toHaveBeenCalledTimes(1)
    })

    act(() => {
      result.current.approve('', onDone)
    })
    await waitFor(() => {
      expect(onDone).toHaveBeenCalledTimes(2)
    })

    expect(bodies).toEqual([{}, {}])
    expect(result.current.errorKey).toBeNull()
  })

  it('approve sends the comment when one is given', async () => {
    let body: unknown
    server.use(
      http.post('/reports/:id/approve', async ({ request }) => {
        body = await request.json()
        return HttpResponse.json(reportDetail('approved'))
      }),
    )
    const { result } = renderHookWithProviders(() => useReviewReport('r-1'))

    act(() => {
      result.current.approve('確認しました')
    })
    await waitFor(() => {
      expect(body).toEqual({ comment: '確認しました' })
    })
  })

  it('reject always sends the required comment', async () => {
    let body: unknown
    server.use(
      http.post('/reports/:id/reject', async ({ request }) => {
        body = await request.json()
        return HttpResponse.json(reportDetail('rejected'))
      }),
    )
    const onDone = vi.fn()
    const { result } = renderHookWithProviders(() => useReviewReport('r-1'))

    act(() => {
      result.current.reject('写真が不足しています', onDone)
    })
    await waitFor(() => {
      expect(onDone).toHaveBeenCalledTimes(1)
    })
    expect(body).toEqual({ comment: '写真が不足しています' })
  })

  it('maps a failed approve to report.review.error and does not call onDone', async () => {
    server.use(
      http.post('/reports/:id/approve', () =>
        HttpResponse.json({ type: `${BASE}/conflict`, title: 'Conflict' }, { status: 409 }),
      ),
    )
    const onDone = vi.fn()
    const { result } = renderHookWithProviders(() => useReviewReport('r-1'))

    act(() => {
      result.current.approve('ok', onDone)
    })
    await waitFor(() => {
      expect(result.current.errorKey).toBe('report.review.error')
    })
    expect(onDone).not.toHaveBeenCalled()
    expect(result.current.isPending).toBe(false)
  })

  it('maps a failed reject to the same error key', async () => {
    server.use(
      http.post('/reports/:id/reject', () =>
        HttpResponse.json({ type: `${BASE}/internal-error`, title: 'Internal' }, { status: 500 }),
      ),
    )
    const { result } = renderHookWithProviders(() => useReviewReport('r-1'))

    act(() => {
      result.current.reject('コメント')
    })
    await waitFor(() => {
      expect(result.current.errorKey).toBe('report.review.error')
    })
  })
})
