import { describe, expect, it } from 'vitest'
import { toTemplate, toTemplateList } from './mapper'

describe('report-template mapper', () => {
  it('maps a template DTO to the model', () => {
    const template = toTemplate({ template_id: 't-1', name: '日報（標準）', is_default: true })
    expect(template.id).toBe('t-1')
    expect(template.name).toBe('日報（標準）')
    expect(template.isDefault).toBe(true)
  })

  it('maps the list envelope', () => {
    const list = toTemplateList({
      items: [{ template_id: 't-1', name: 'A', is_default: false }],
    })
    expect(list).toHaveLength(1)
    expect(list[0]?.name).toBe('A')
  })
})
