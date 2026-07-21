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
  { to: '/', labelKey: 'common.nav.dashboard', icon: '◫', end: true },
  { to: '/reports', labelKey: 'common.nav.reportList', icon: '▤' },
]

const ADMIN_NAV: NavItem[] = [
  { to: '/templates', labelKey: 'common.nav.templates', icon: '◳' },
  { to: '/users', labelKey: 'common.nav.users', icon: '◑' },
  { to: '/audit-logs', labelKey: 'common.nav.audit', icon: '◔' },
  { to: '/export', labelKey: 'common.nav.export', icon: '⬇' },
  { to: '/settings', labelKey: 'common.nav.settings', icon: '⚙' },
]

/**
 * Per-route layout for <main>, matching the two intentional page-header types
 * (design handoff `part-page-headers.html`):
 *  - 'toolbar' (作業卓): pane is full-height, no padding; the page pins its own
 *    white toolbar (`flex-none`) and scrolls only its body.
 *  - 'document' (書類): the pane scrolls as one document; the page owns its
 *    centered column and 30px padding.
 *  - 'default': ordinary scrolling content with shell padding (dashboard, etc.).
 */
type MainMode = 'toolbar' | 'document' | 'plain' | 'default'

const TOOLBAR_PATHS = new Set(['/reports', '/users', '/audit-logs', '/templates', '/templates/new'])

function mainMode(path: string): MainMode {
  if (TOOLBAR_PATHS.has(path) || /^\/templates\/[^/]+\/edit$/.test(path)) return 'toolbar'
  if (path === '/export' || path === '/settings') return 'document'
  if (path === '/') return 'plain'
  return 'default'
}

const MAIN_CLASS: Record<MainMode, string> = {
  toolbar: 'flex min-h-0 flex-1 flex-col overflow-hidden',
  document: 'min-h-0 flex-1 overflow-y-auto',
  // 'plain' lets the page own its padding (dashboard 26/30/40); 'default' keeps p-6.
  plain: 'min-h-0 flex-1 overflow-y-auto',
  default: 'min-h-0 flex-1 overflow-y-auto p-6',
}

const TITLE_BY_PATH: Record<string, MessageKey> = {
  '/': 'common.nav.dashboard',
  '/reports': 'common.nav.reportList',
  '/templates': 'common.nav.templates',
  '/users': 'common.nav.users',
  '/audit-logs': 'common.nav.audit',
  '/export': 'common.nav.export',
  '/settings': 'common.nav.settings',
}

// Breadcrumb shown under the topbar title (design handoff topbar spec).
const CRUMB_BY_PATH: Record<string, MessageKey> = {
  '/': 'shell.crumb.dashboard',
  '/reports': 'shell.crumb.reports',
  '/templates': 'shell.crumb.templates',
  '/users': 'shell.crumb.users',
  '/audit-logs': 'shell.crumb.audit',
  '/export': 'shell.crumb.export',
  '/settings': 'shell.crumb.settings',
}

interface PageMeta {
  title: MessageKey
  crumb?: MessageKey
}

/** Resolve the topbar title + crumb, including sub-routes (new / edit pages). */
function resolveMeta(path: string): PageMeta {
  const exact = TITLE_BY_PATH[path]
  if (exact !== undefined) {
    // `crumb` is omitted (not set to `undefined`) when there's no entry, since
    // PageMeta.crumb has no `| undefined` in its type (exactOptionalPropertyTypes).
    const crumb = CRUMB_BY_PATH[path]
    return crumb !== undefined ? { title: exact, crumb } : { title: exact }
  }
  if (path.startsWith('/templates'))
    return { title: 'template.editor.title', crumb: 'shell.crumb.templates' }
  if (path.startsWith('/users')) return { title: 'common.nav.users', crumb: 'shell.crumb.users' }
  if (path.startsWith('/reports'))
    return { title: 'common.nav.reportList', crumb: 'shell.crumb.reports' }
  return { title: 'common.app.name' }
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
    'flex items-center gap-2.5 rounded-x-input px-3 py-2 text-sm text-text-inverse/85 transition-colors',
    isActive ? 'bg-white/15 font-semibold text-text-inverse' : 'hover:bg-white/10',
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
  const { title: titleKey, crumb: crumbKey } = resolveMeta(location.pathname)
  const mode = mainMode(location.pathname)
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
      <aside className="flex w-61 flex-none flex-col bg-gradient-to-b from-x-accent-deep to-x-accent-deep-2 px-3 py-4 text-text-inverse">
        <div className="flex items-center gap-2.5 px-2 pb-4">
          <span className="grid h-9 w-9 place-items-center rounded-x-input bg-gradient-to-br from-accent to-x-accent-deep text-base font-extrabold">
            N
          </span>
          <div className="min-w-0">
            <div className="text-base font-extrabold tracking-wide">{t('common.app.name')}</div>
            {org.data !== undefined && (
              <div className="truncate text-xs text-text-inverse/55">{org.data.name}</div>
            )}
          </div>
        </div>

        <p className="px-3 pt-2 pb-1 text-xs font-semibold uppercase tracking-widest text-text-inverse/55">
          {t('shell.group.main')}
        </p>
        {/* end defaults to false in react-router; `?? false` keeps that behavior
            while satisfying exactOptionalPropertyTypes (NavLinkProps.end has no
            `| undefined` in its type). */}
        {MAIN_NAV.map((item) => (
          <NavLink key={item.to} to={item.to} end={item.end ?? false} className={navLinkClass}>
            <span className="w-5 text-center opacity-90">{item.icon}</span>
            <span className="flex-1">{t(item.labelKey)}</span>
            {item.to === '/reports' && pendingCount > 0 && (
              <span className="grid h-5 min-w-5 place-items-center rounded-x-pill bg-accent px-1.5 text-xs font-bold text-text-inverse">
                {pendingCount}
              </span>
            )}
          </NavLink>
        ))}

        {canManage && (
          <>
            <p className="px-3 pt-4 pb-1 text-xs font-semibold uppercase tracking-widest text-text-inverse/55">
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
            className="flex w-full items-center gap-2.5 rounded-x-input px-3 py-2 text-sm text-text-inverse/85 hover:bg-white/10"
          >
            <span className="w-5 text-center">📱</span>
            {t('shell.openSubmitterApp')}
          </Link>
          <button
            type="button"
            onClick={signOut}
            className="flex w-full items-center gap-2.5 rounded-x-input px-3 py-2 text-sm text-text-inverse/85 hover:bg-white/10"
          >
            <span className="w-5 text-center">⏻</span>
            {t('common.actions.signOut')}
          </button>
          {user !== null && (
            <div className="px-3 pt-2 text-xs text-text-inverse/70">
              {user.name}
              <span className="block text-text-inverse/45">{user.role}</span>
            </div>
          )}
        </div>
      </aside>

      {/* ── main column ─────────────────────────────────────────── */}
      <div className="flex min-w-0 flex-1 flex-col">
        <header className="flex h-15 flex-none items-center gap-3.5 border-b border-border bg-surface-raised px-6.5">
          <div className="min-w-0">
            <h1 className="text-base font-bold leading-tight text-text-primary">{t(titleKey)}</h1>
            {crumbKey !== undefined && (
              <p className="text-caption text-text-faint">{t(crumbKey)}</p>
            )}
          </div>

          <div className="flex-1" />

          <div className="hidden w-57.5 items-center gap-2 rounded-x-pill border border-border bg-surface-overlay px-3.5 py-2 text-ui text-x-fg-faint-2 md:flex">
            <span aria-hidden>⌕</span>
            <input
              type="search"
              placeholder={t('shell.search.placeholder')}
              className="w-full bg-transparent text-ui text-text-primary outline-none placeholder:text-x-fg-faint-2"
            />
          </div>

          <div className="flex items-center rounded-x-pill bg-surface p-0.75 text-xs font-bold">
            <button
              type="button"
              onClick={() => {
                setLocale('ja')
              }}
              className={cn(
                'rounded-x-input px-2.75 py-1.25',
                locale === 'ja'
                  ? 'bg-surface-raised text-on-accent shadow-x-card'
                  : 'text-x-fg-faint-2',
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
                'rounded-x-input px-2.75 py-1.25',
                locale === 'en'
                  ? 'bg-surface-raised text-on-accent shadow-x-card'
                  : 'text-x-fg-faint-2',
              )}
            >
              EN
            </button>
          </div>

          <div className="relative">
            <button
              type="button"
              aria-label={t('shell.notifications.title')}
              onClick={() => {
                setBellOpen((v) => !v)
              }}
              className="relative grid h-9 w-9 place-items-center rounded-x-pill bg-surface-overlay text-base text-text-muted hover:bg-surface-overlay"
            >
              <span aria-hidden>◔</span>
              {unread > 0 && (
                <span className="absolute -right-0.5 -top-0.5 grid h-4.25 min-w-4.25 place-items-center rounded-x-pill border-2 border-surface-raised bg-x-rejected px-1 text-micro font-bold text-text-inverse">
                  {unread}
                </span>
              )}
            </button>

            {bellOpen && (
              <div className="absolute right-0 top-12 w-86 overflow-hidden rounded-x-card border border-border bg-surface-raised shadow-x-modal animate-nfpop">
                <NotificationList
                  items={notifications}
                  markAllLabel={t('shell.notifications.markAll')}
                  unreadLabel={t('shell.notifications.unread')}
                  emptyLabel={t('shell.notifications.empty')}
                  onSelect={selectNotification}
                  onMarkAllRead={markAllRead}
                />
              </div>
            )}
          </div>

          <span className="grid h-9 w-9 place-items-center rounded-x-pill bg-accent-soft text-ui font-bold text-on-accent">
            {user?.name.slice(0, 1) ?? '?'}
          </span>
        </header>

        <main className={MAIN_CLASS[mode]}>
          <Outlet />
        </main>
      </div>
    </div>
  )
}
