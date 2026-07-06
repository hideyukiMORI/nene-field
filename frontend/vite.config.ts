import path from 'node:path'
import { fileURLToPath } from 'node:url'
import tailwindcss from '@tailwindcss/vite'
import react from '@vitejs/plugin-react'
import { defineConfig, loadEnv } from 'vite'

const dirname = path.dirname(fileURLToPath(import.meta.url))

// The PHP API runs same-origin in production (Tier A → public_html/admin). In
// dev, Vite serves the SPA and proxies API paths to the running PHP app. Override
// the target with VITE_API_TARGET when the app listens elsewhere.
// NeNe Field fixed dev: frontend 5192, API 9200 (php -S / docker stack).
export default defineConfig(({ command, mode }) => {
  const env = loadEnv(mode, dirname, 'VITE_')
  const target = env['VITE_API_TARGET'] ?? 'http://127.0.0.1:9200'

  return {
    plugins: [react(), tailwindcss()],
    // Relative asset paths for the production bundle so it can be served from the
    // /admin sub-path (Tier A). The dev server needs an absolute base ('/').
    base: command === 'build' ? './' : '/',
    resolve: {
      alias: {
        '@': path.resolve(dirname, './src'),
        '@tests': path.resolve(dirname, './tests'),
      },
    },
    build: {
      outDir: path.resolve(dirname, '../public_html/admin'),
      emptyOutDir: true,
    },
    server: {
      port: 5192,
      proxy: {
        '/health': { target, changeOrigin: true },
        '/auth': { target, changeOrigin: true },
        '/reports': { target, changeOrigin: true },
        '/users': { target, changeOrigin: true },
        '/organizations': { target, changeOrigin: true },
        '/templates': { target, changeOrigin: true },
        '/export': { target, changeOrigin: true },
        '/audit-events': { target, changeOrigin: true },
      },
    },
  }
})
