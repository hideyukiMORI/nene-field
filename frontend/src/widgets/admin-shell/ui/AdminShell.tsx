import { useState } from 'react'
import { useSyncExternalStore } from 'react'
import { NavLink, Outlet, useLocation } from 'react-router-dom'
import { canManageOrganization } from '@/entities/auth/enum'
import { getCurrentUser, signOut, subscribeCurrentUser } from '@/entities/auth/session'
import { useTranslation } from '@/shared/i18n'
import type { MessageKey } from '@/shared/i18n'
import { cn } from '@/shared/lib/cn'
import { NotificationList } from '@/shared/ui'
import type { NotificationItem } from '@/shared/ui'

interface NavItem {
  to: string
  labelKey: MessageKey
  icon: string
  end?: boolean
}

const MAIN_NAV: NavItem[] = [
  { to: '/', labelKey: 'common.nav.dashboard', icon: '⌂', end: true },
  { to: '/reports', labelKey: 'common.nav.reportList', icon: '▤' },
]

const ADMIN_NAV: NavItem[] = [
  { to: '/templates', labelKey: 'common.nav.templates', icon: '◫' },
  { to: '/users', labelKey: 'common.nav.users', icon: '⚇' },
  { to: '/audit-logs', labelKey: 'common.nav.audit', icon: '◳' },
  { to: '/export', labelKey: 'common.nav.export', icon: '⬇' },
  { to: '/settings', labelKey: 'common.nav.settings', icon: '⚙' },
]

const TITLE_BY_PATH: Record<string, MessageKey> = {
  '/': 'common.nav.dashboard',
  '/reports': 'common.nav.reportList',
  '/templates': 'common.nav.templates',
  '/users': 'common.nav.users',
  '/audit-logs': 'common.nav.audit',
  '/export': 'common.nav.export',
  '/settings': 'common.nav.settings',
}

// Placeholder notifications until the notification entity is wired (task #18).
const MOCK_NOTIFICATIONS: NotificationItem[] = []

function navLinkClass({ isActive }: { isActive: boolean }): string {
  return cn(
    'flex items-center gap-2.5 rounded-input px-3 py-2 text-sm text-fg-inverse/85 transition-colors',
    isActive ? 'bg-white/15 font-semibold text-fg-inverse' : 'hover:bg-white/10',
  )
}

export function AdminShell() {
  const { t, locale, setLocale } = useTranslation()
  const user = useSyncExternalStore(subscribeCurrentUser, getCurrentUser)
  const location = useLocation()
  const [bellOpen, setBellOpen] = useState(false)

  const canManage = user !== null && canManageOrganization(user.role)
  const titleKey = TITLE_BY_PATH[location.pathname] ?? 'common.app.name'
  const unread = MOCK_NOTIFICATIONS.filter((n) => n.unread).length

  return (
    <div className="flex h-screen overflow-hidden bg-surface">
      {/* ── sidebar ─────────────────────────────────────────────── */}
      <aside className="flex w-61 flex-none flex-col bg-gradient-to-b from-accent-deep to-accent-deep-2 px-3 py-4 text-fg-inverse">
        <div className="flex items-center gap-2.5 px-2 pb-4">
          <span className="grid h-8 w-8 place-items-center rounded-input bg-gradient-to-br from-accent to-accent-deep text-base font-extrabold">
            N
          </span>
          <span className="text-base font-extrabold tracking-wide">{t('common.app.name')}</span>
        </div>

        <p className="px-3 pt-2 pb-1 text-xs font-semibold uppercase tracking-widest text-fg-inverse/55">
          {t('shell.group.main')}
        </p>
        {MAIN_NAV.map((item) => (
          <NavLink key={item.to} to={item.to} end={item.end} className={navLinkClass}>
            <span className="w-5 text-center opacity-90">{item.icon}</span>
            {t(item.labelKey)}
          </NavLink>
        ))}

        {canManage && (
          <>
            <p className="px-3 pt-4 pb-1 text-xs font-semibold uppercase tracking-widest text-fg-inverse/55">
              {t('shell.group.admin')}
            </p>
            {ADMIN_NAV.map((item) => (
              <NavLink key={item.to} to={item.to} className={navLinkClass}>
                <span className="w-5 text-center opacity-90">{item.icon}</span>
                {t(item.labelKey)}
              </NavLink>
            ))}
          </>
        )}

        <div className="mt-auto border-t border-white/10 pt-3">
          <button
            type="button"
            onClick={signOut}
            className="flex w-full items-center gap-2.5 rounded-input px-3 py-2 text-sm text-fg-inverse/85 hover:bg-white/10"
          >
            <span className="w-5 text-center">⏻</span>
            {t('common.actions.signOut')}
          </button>
          {user !== null && (
            <div className="px-3 pt-2 text-xs text-fg-inverse/70">
              {user.name}
              <span className="block text-fg-inverse/45">{user.role}</span>
            </div>
          )}
        </div>
      </aside>

      {/* ── main column ─────────────────────────────────────────── */}
      <div className="flex min-w-0 flex-1 flex-col">
        <header className="flex h-15 flex-none items-center gap-4 border-b border-border bg-surface-raised px-6">
          <h1 className="text-base font-bold text-fg">{t(titleKey)}</h1>
          <div className="relative ml-auto flex items-center gap-2">
            <button
              type="button"
              onClick={() => {
                setLocale(locale === 'ja' ? 'en' : 'ja')
              }}
              className="rounded-pill border border-border-strong px-3 py-1.5 text-xs font-semibold text-fg-muted hover:bg-surface-overlay"
            >
              {locale === 'ja' ? 'EN' : '日本語'}
            </button>

            <button
              type="button"
              aria-label={t('shell.notifications.title')}
              onClick={() => {
                setBellOpen((v) => !v)
              }}
              className="relative grid h-9 w-9 place-items-center rounded-pill text-fg-muted hover:bg-surface-overlay"
            >
              <span aria-hidden>◔</span>
              {unread > 0 && (
                <span className="absolute -right-0.5 -top-0.5 grid h-4 min-w-4 place-items-center rounded-pill border-2 border-surface-raised bg-rejected px-1 text-xs font-bold text-fg-inverse">
                  {unread}
                </span>
              )}
            </button>

            <span className="grid h-8 w-8 place-items-center rounded-pill bg-accent-soft text-xs font-bold text-accent-ink">
              {user?.name.slice(0, 2) ?? '??'}
            </span>

            {bellOpen && (
              <div className="absolute right-0 top-12 w-80 overflow-hidden rounded-card border border-border bg-surface-raised shadow-modal animate-nfpop">
                <NotificationList
                  items={MOCK_NOTIFICATIONS}
                  markAllLabel={t('shell.notifications.markAll')}
                  emptyLabel={t('shell.notifications.empty')}
                  onSelect={() => {
                    setBellOpen(false)
                  }}
                  onMarkAllRead={() => {
                    setBellOpen(false)
                  }}
                />
              </div>
            )}
          </div>
        </header>

        <main className="min-h-0 flex-1 overflow-auto p-6">
          <Outlet />
        </main>
      </div>
    </div>
  )
}
