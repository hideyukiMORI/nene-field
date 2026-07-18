import { describe, expect, it } from 'vitest'
import { env } from './env'

/**
 * T1 (#118): env.ts is the single validated gateway to import.meta.env — the
 * zod parse runs at module load, so a schema violation would crash the app at
 * boot. Pins that the module loads and exposes exactly the two boolean flags.
 * Extend this when VITE_* values are added to the schema.
 */
describe('env', () => {
  it('loads under the test environment and exposes boolean build flags', () => {
    expect(typeof env.isDev).toBe('boolean')
    expect(typeof env.isProd).toBe('boolean')
    expect(env.isDev).not.toBe(env.isProd)
  })

  it('exposes exactly the public shape (no raw import.meta.env leakage)', () => {
    expect(Object.keys(env).sort()).toEqual(['isDev', 'isProd'])
  })
})
