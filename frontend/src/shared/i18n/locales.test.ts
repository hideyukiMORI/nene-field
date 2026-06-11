import { describe, expect, it } from 'vitest'
import { enMessages } from './messages/en'
import { jaMessages } from './messages/ja'
import { resolveLocale } from './locales'

describe('locale catalogs', () => {
  it('en has parity with the ja master (every key present in both)', () => {
    const jaKeys = Object.keys(jaMessages).sort()
    const enKeys = Object.keys(enMessages).sort()
    expect(enKeys).toStrictEqual(jaKeys)
  })

  it('every en value is a non-empty string', () => {
    for (const [key, value] of Object.entries(enMessages)) {
      expect(value, key).toBeTypeOf('string')
      expect(value.length, key).toBeGreaterThan(0)
    }
  })
})

describe('resolveLocale', () => {
  it('maps en-* to en and everything else to ja', () => {
    expect(resolveLocale('en-US')).toBe('en')
    expect(resolveLocale('EN')).toBe('en')
    expect(resolveLocale('ja-JP')).toBe('ja')
    expect(resolveLocale(null)).toBe('ja')
    expect(resolveLocale(undefined)).toBe('ja')
  })
})
