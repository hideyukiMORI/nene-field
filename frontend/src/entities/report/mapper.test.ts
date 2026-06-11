import { describe, expect, it } from 'vitest'
import type { ReportSummaryDto } from './api-types'
import { toReportList, toReportSummary } from './mapper'

const dto: ReportSummaryDto = {
  report_id: 'r-1',
  user_id: 'u-1',
  user_name: '田中太郎',
  title: '現場A 報告',
  work_date: '2026-06-11',
  status: 'approved',
  created_at: '2026-06-11 08:00:00',
}

describe('report mapper', () => {
  it('maps a summary DTO with snake_case → camelCase model', () => {
    const model = toReportSummary(dto)
    expect(model.id).toBe('r-1')
    expect(model.userName).toBe('田中太郎')
    expect(model.status).toBe('approved')
    expect(model.tags).toStrictEqual([])
    expect(model.projectCode).toBeNull()
  })

  it('fails closed on an unknown status', () => {
    expect(toReportSummary({ ...dto, status: 'archived' }).status).toBe('draft')
  })

  it('maps the list envelope and defaults total to item count', () => {
    const list = toReportList({ items: [dto], limit: 20, offset: 0 })
    expect(list.total).toBe(1)
    expect(list.items).toHaveLength(1)
  })
})
