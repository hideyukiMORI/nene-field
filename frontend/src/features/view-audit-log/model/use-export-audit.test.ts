import { act, waitFor } from '@testing-library/react'
import { HttpResponse, http } from 'msw'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { renderHookWithProviders } from '@tests/render/render-with-providers'
import { server } from '@tests/msw/server'
import { useExportAudit } from './use-export-audit'

const BASE = 'https://nene-field.dev/problems'

function csvHandler(seenParams: URLSearchParams[]) {
  return http.get('/audit-events/export', ({ request }) => {
    seenParams.push(new URL(request.url).searchParams)
    return new HttpResponse('event_id,event_name\n', {
      headers: { 'Content-Type': 'text/csv' },
    })
  })
}

/**
 * T1 (#116): the export model owns the query-parameter contract (an empty
 * entity type is omitted, not sent as ''), the download side effect, and the
 * error/retry state machine. Requests are pinned at the MSW boundary.
 */
describe('useExportAudit', () => {
  afterEach(() => {
    vi.restoreAllMocks()
  })

  it('sends occurred_from/occurred_to and omits entity_type when empty', async () => {
    const seen: URLSearchParams[] = []
    server.use(csvHandler(seen))
    const click = vi.spyOn(HTMLAnchorElement.prototype, 'click').mockImplementation(() => {})
    const { result } = renderHookWithProviders(() => useExportAudit())

    act(() => {
      result.current.exportCsv('2026-07-01', '2026-07-18', '')
    })
    expect(result.current.isExporting).toBe(true)
    await waitFor(() => {
      expect(result.current.isExporting).toBe(false)
    })

    expect(click).toHaveBeenCalledTimes(1)
    expect(seen).toHaveLength(1)
    expect(seen[0]?.get('occurred_from')).toBe('2026-07-01')
    expect(seen[0]?.get('occurred_to')).toBe('2026-07-18')
    expect(seen[0]?.has('entity_type')).toBe(false)
    expect(result.current.errorKey).toBeNull()
    expect(result.current.isExporting).toBe(false)
  })

  it('sends entity_type when one is selected', async () => {
    const seen: URLSearchParams[] = []
    server.use(csvHandler(seen))
    const click = vi.spyOn(HTMLAnchorElement.prototype, 'click').mockImplementation(() => {})
    const { result } = renderHookWithProviders(() => useExportAudit())

    act(() => {
      result.current.exportCsv('2026-07-01', '2026-07-18', 'Report')
    })
    await waitFor(() => {
      expect(click).toHaveBeenCalledTimes(1)
    })
    await waitFor(() => {
      expect(result.current.isExporting).toBe(false)
    })

    expect(seen[0]?.get('entity_type')).toBe('Report')
  })

  it('maps a failed export to audit.export.error and clears it on a successful retry', async () => {
    vi.spyOn(HTMLAnchorElement.prototype, 'click').mockImplementation(() => {})
    server.use(
      http.get('/audit-events/export', () =>
        HttpResponse.json({ type: `${BASE}/internal-error`, title: 'Internal' }, { status: 500 }),
      ),
    )
    const { result } = renderHookWithProviders(() => useExportAudit())

    act(() => {
      result.current.exportCsv('2026-07-01', '2026-07-18', '')
    })
    await waitFor(() => {
      expect(result.current.errorKey).toBe('audit.export.error')
    })
    expect(result.current.isExporting).toBe(false)

    const seen: URLSearchParams[] = []
    server.use(csvHandler(seen))
    act(() => {
      result.current.exportCsv('2026-07-01', '2026-07-18', '')
    })
    await waitFor(() => {
      expect(result.current.errorKey).toBeNull()
    })
    expect(seen).toHaveLength(1)
  })
})
