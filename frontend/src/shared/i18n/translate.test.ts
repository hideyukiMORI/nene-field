import { describe, expect, it } from 'vitest'
import { jaMessages } from './messages/ja'
import { translate } from './translate'

describe('translate', () => {
  it('returns the value from the active catalog', () => {
    expect(translate(jaMessages, 'common.actions.signIn')).toBe('ログイン')
  })

  it('falls back to the ja master for a missing key', () => {
    expect(translate({}, 'common.actions.signIn')).toBe(jaMessages['common.actions.signIn'])
  })

  it('interpolates {{name}} placeholders', () => {
    const catalog = { 'report.list.title': 'Hello {{name}} ({{count}})' } as const
    expect(translate(catalog, 'report.list.title', { name: 'Aki', count: 3 })).toBe('Hello Aki (3)')
  })

  it('leaves an unmatched placeholder intact', () => {
    const catalog = { 'report.list.title': 'Hi {{missing}}' } as const
    expect(translate(catalog, 'report.list.title', {})).toBe('Hi {{missing}}')
  })
})
