import { describe, expect, it } from 'vitest'
import type { TemplateDto } from './api-types'
import { toTemplate, toTemplateList } from './mapper'

const dto: TemplateDto = {
  template_id: 't-1',
  organization_id: 'org-1',
  name: '日報（標準）',
  description: '標準テンプレート',
  fields: [
    { name: 'summary', label: '作業内容', type: 'textarea', required: true },
    { name: 'weather', label: '天候', type: 'select', required: false, options: ['晴れ', '雨'] },
  ],
  is_default: true,
  created_at: '2026-06-01 00:00:00',
  updated_at: '2026-06-01 00:00:00',
}

describe('report-template mapper', () => {
  it('maps a full template incl. fields', () => {
    const template = toTemplate(dto)
    expect(template.id).toBe('t-1')
    expect(template.isDefault).toBe(true)
    expect(template.fields).toHaveLength(2)
    expect(template.fields[0]?.type).toBe('textarea')
    expect(template.fields[1]?.options).toStrictEqual(['晴れ', '雨'])
  })

  it('defaults options and falls closed on an unknown field type', () => {
    const mapped = toTemplate({
      ...dto,
      fields: [{ name: 'x', label: 'X', type: 'rating', required: false }],
    })
    expect(mapped.fields[0]?.type).toBe('text')
    expect(mapped.fields[0]?.options).toStrictEqual([])
  })

  it('maps the list envelope', () => {
    expect(toTemplateList({ items: [dto] })).toHaveLength(1)
  })
})
