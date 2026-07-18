import { afterEach, describe, expect, it, vi } from 'vitest'
import { triggerDownload } from './trigger-download'

/**
 * T1 (#116): pins the full object-URL lifecycle — create, click an anchor
 * carrying the filename, leave no DOM residue, then revoke. The setup file
 * stubs URL.createObjectURL to return 'blob:mock-url'.
 */
describe('triggerDownload', () => {
  afterEach(() => {
    vi.restoreAllMocks()
  })

  it('clicks a transient anchor with the object URL and filename, then cleans up', () => {
    const clicked: { href: string; download: string }[] = []
    vi.spyOn(HTMLAnchorElement.prototype, 'click').mockImplementation(function (
      this: HTMLAnchorElement,
    ) {
      clicked.push({ href: this.href, download: this.download })
      expect(document.body.contains(this)).toBe(true)
    })
    const revoke = vi.spyOn(URL, 'revokeObjectURL')

    triggerDownload(new Blob(['a,b\n1,2'], { type: 'text/csv' }), 'audit_events.csv')

    expect(clicked).toEqual([{ href: 'blob:mock-url', download: 'audit_events.csv' }])
    expect(document.querySelector('a[download]')).toBeNull()
    expect(revoke).toHaveBeenCalledWith('blob:mock-url')
  })
})
