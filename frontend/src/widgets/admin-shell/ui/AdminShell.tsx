import { useState } from 'react'
import { useSyncExternalStore } from 'react'
import { Link, NavLink, Outlet, useLocation, useNavigate } from 'react-router-dom'
import { canManageOrganization } from '@/entities/auth/enum'
import { getCurrentUser, signOut, subscribeCurrentUser } from '@/entities/auth/session'
import { useOrganizationQuery } from '@/entities/organization'
import { useReportListQuery } from '@/entities/report'
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

// Seed notifications (dummy data per design handoff §0; wire to an entity later).
const SEED_NOTIFICATIONS: NotificationItem[] = [
  {
    id: 'n1',
    type: 'submitted',
    title: '山田 太郎さんが日報を提出',
    sub: '現場A 基礎打設',
    time: '5分前',
    unread: true,
  },
  {
    id: 'n2',
    type: 'rejected',
    title: '配筋検査が差し戻されました',
    sub: '写真が不足しています',
    time: '1時間前',
    unread: true,
  },
  {
    id: 'n3',
    type: 'approved',
    title: '現場B 仮設足場を承認',
    sub: '田中 一郎',
    time: '昨日',
    unread: false,
  },
]

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
  const navigate = useNavigate()
  const [bellOpen, setBellOpen] = useState(false)
  const [notifications, setNotifications] = useState<NotificationItem[]>(SEED_NOTIFICATIONS)

  const orgId = user?.organizationId ?? ''
  const org = useOrganizationQuery(orgId, { enabled: orgId !== '' })
  const reports = useReportListQuery({ limit: 100, offset: 0 })
  const pendingCount = (reports.data?.items ?? []).filter((r) => r.status === 'submitted').length

  const canManage = user !== null && canManageOrganization(user.role)
  const titleKey = TITLE_BY_PATH[location.pathname] ?? 'common.app.name'
  const unread = notifications.filter((n) => n.unread).length

  const markAllRead = (): void => {
    setNotifications((prev) => prev.map((n) => ({ ...n, unread: false })))
  }
  const selectNotification = (id: string): void => {
    setNotifications((prev) => prev.map((n) => (n.id === id ? { ...n, unread: false } : n)))
    setBellOpen(false)
    void navigate('/reports')
  }

  return (
    <div className="flex h-screen overflow-hidden bg-surface">
      {/* ── sidebar ─────────────────────────────────────────────── */}
      <aside className="flex w-61 flex-none flex-col bg-gradient-to-b from-accent-deep to-accent-deep-2 px-3 py-4 text-fg-inverse">
        <div className="flex items-center gap-2.5 px-2 pb-4">
          <span className="grid h-9 w-9 place-items-center rounded-input bg-gradient-to-br from-accent to-accent-deep text-base font-extrabold">
            N
          </span>
          <div className="min-w-0">
            <div className="text-base font-extrabold tracking-wide">{t('common.app.name')}</div>
            {org.data !== undefined && (
              <div className="truncate text-xs text-fg-inverse/55">{org.data.name}</div>
            )}
          </div>
        </div>

        <p className="px-3 pt-2 pb-1 text-xs font-semibold uppercase tracking-widest text-fg-inverse/55">
          {t('shell.group.main')}
        </p>
        {MAIN_NAV.map((item) => (
          <NavLink key={item.to} to={item.to} end={item.end} className={navLinkClass}>
            <span className="w-5 text-center opacity-90">{item.icon}</span>
            <span className="flex-1">{t(item.labelKey)}</span>
            {item.to === '/reports' && pendingCount > 0 && (
              <span className="grid h-5 min-w-5 place-items-center rounded-pill bg-accent px-1.5 text-xs font-bold text-fg-inverse">
                {pendingCount}
              </span>
            )}
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
          <Link
            to="/"
            className="flex w-full items-center gap-2.5 rounded-input px-3 py-2 text-sm text-fg-inverse/85 hover:bg-white/10"
          >
            <span className="w-5 text-center">📱</span>
            {t('shell.openSubmitterApp')}
          </Link>
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

          <div className="mx-auto hidden w-full max-w-sm items-center gap-2 rounded-pill border border-border bg-surface-overlay px-3.5 py-2 md:flex">
            <span className="text-fg-faint">⌕</span>
            <input
              type="search"
              placeholder={t('shell.search.placeholder')}
              className="w-full bg-transparent text-sm text-fg outline-none placeholder:text-fg-faint"
            />
          </div>

          <div className="relative ml-auto flex items-center gap-2">
            <div className="flex items-center rounded-pill bg-surface-overlay p-0.5 text-xs font-semibold">
              <button
                type="button"
                onClick={() => {
                  setLocale('ja')
                }}
                className={cn(
                  'rounded-pill px-2.5 py-1',
                  locale === 'ja'
                    ? 'bg-surface-raised text-accent-ink shadow-card'
                    : 'text-fg-muted',
                )}
              >
                日本語
              </button>
              <button
                type="button"
                onClick={() => {
                  setLocale('en')
                }}
                className={cn(
                  'rounded-pill px-2.5 py-1',
                  locale === 'en'
                    ? 'bg-surface-raised text-accent-ink shadow-card'
                    : 'text-fg-muted',
                )}
              >
                EN
              </button>
            </div>

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
                  items={notifications}
                  markAllLabel={t('shell.notifications.markAll')}
                  emptyLabel={t('shell.notifications.empty')}
                  onSelect={selectNotification}
                  onMarkAllRead={markAllRead}
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
