import { describe, expect, it } from 'vitest'
import type { ReportResponseDto, ReportSummaryDto } from './api-types'
import { toReportDetail, toReportList, toReportSummary } from './mapper'

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

describe('toReportDetail', () => {
  const detailDto: ReportResponseDto = {
    report_id: 'r-1',
    organization_id: 'org-1',
    user_id: 'u-1',
    user_name: '田中太郎',
    title: '現場A 報告',
    body: '本文',
    work_date: '2026-06-11',
    status: 'submitted',
    created_at: '2026-06-11 08:00:00',
    updated_at: '2026-06-11 08:00:00',
    attachments: [
      {
        attachment_id: 'a-1',
        filename: 'photo.png',
        mime_type: 'image/png',
        file_size: 1024,
        sha256: 'abc',
        created_at: '2026-06-11 08:30:00',
      },
    ],
  }

  it('maps the full report incl. embedded attachments', () => {
    const detail = toReportDetail(detailDto)
    expect(detail.id).toBe('r-1')
    expect(detail.body).toBe('本文')
    expect(detail.approverComment).toBeNull()
    expect(detail.attachments).toHaveLength(1)
    expect(detail.attachments[0]?.filename).toBe('photo.png')
    expect(detail.attachments[0]?.fileSize).toBe(1024)
  })

  it('defaults missing optional collections', () => {
    // exactOptionalPropertyTypes forbids assigning `undefined` to an optional
    // (non-`| undefined`) DTO field, so the "missing" case is expressed by
    // deleting the keys entirely (the sanctioned way to unset an optional
    // property under exactOptionalPropertyTypes) rather than setting them to
    // undefined.
    const withoutCollections: ReportResponseDto = { ...detailDto }
    delete withoutCollections.attachments
    delete withoutCollections.tags
    const detail = toReportDetail(withoutCollections)
    expect(detail.attachments).toStrictEqual([])
    expect(detail.tags).toStrictEqual([])
  })
})
