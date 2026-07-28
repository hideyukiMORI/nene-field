import { defineConfig, devices } from '@playwright/test'

// Default 5193 — the NeNe Field frontend dev port (5192, the "92 lane") + 1, so a
// running `npm run dev` is never disturbed. Override with E2E_PORT.
const PORT = Number(process.env.E2E_PORT ?? 5193)

/**
 * Browser tests for the main flows. The app talks to the API over relative paths,
 * which each spec stubs via `page.route` — no live backend is needed, so the tests
 * are deterministic and stay focused on UI behaviour (auth, validation, navigation).
 * The session token is held in memory, so it is lost on reload: specs sign in
 * through the form and then move around by clicking inside the SPA
 * (see e2e/helpers/auth.ts).
 */
export default defineConfig({
  testDir: './e2e',
  fullyParallel: true,
  forbidOnly: Boolean(process.env.CI),
  retries: process.env.CI ? 1 : 0,
  workers: process.env.CI ? 1 : undefined,
  reporter: process.env.CI ? 'list' : 'html',
  timeout: 60_000,
  expect: { timeout: 10_000 },
  use: {
    baseURL: `http://localhost:${String(PORT)}`,
    // The app picks its locale from navigator.language; pin it to Japanese (the
    // primary locale) so the specs can assert on the ja message catalog.
    locale: 'ja-JP',
    trace: 'on-first-retry',
  },
  projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'] } }],
  webServer: {
    command: `npx vite --port ${String(PORT)} --strictPort`,
    url: `http://localhost:${String(PORT)}`,
    reuseExistingServer: !process.env.CI,
    timeout: 120_000,
  },
})
