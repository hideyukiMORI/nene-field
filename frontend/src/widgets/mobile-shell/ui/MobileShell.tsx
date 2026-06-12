import { Link, NavLink, Outlet, useLocation } from 'react-router-dom'
import { useTranslation } from '@/shared/i18n'
import type { MessageKey } from '@/shared/i18n'
import { cn } from '@/shared/lib/cn'

interface Tab {
  to: string
  labelKey: MessageKey
  icon: string
  end?: boolean
}

const TABS: Tab[] = [
  { to: '/', labelKey: 'mobile.tab.home', icon: '⌂', end: true },
  { to: '/reports', labelKey: 'mobile.tab.reports', icon: '▤' },
  { to: '/account', labelKey: 'mobile.tab.account', icon: '⚇' },
]

// The FAB (new report) shows on the list-oriented tabs, not deep in a flow.
const FAB_PATHS = new Set(['/', '/reports'])

function tabClass({ isActive }: { isActive: boolean }): string {
  return cn(
    'flex flex-1 flex-col items-center gap-0.5 py-2 pb-5 text-xs',
    isActive ? 'font-bold text-accent' : 'text-fg-faint-2',
  )
}

/**
 * Submitter mobile shell (design handoff §2.1): full-viewport screen stack with a
 * bottom tab bar (home / my reports / account) and a floating action button.
 */
export function MobileShell() {
  const { t } = useTranslation()
  const location = useLocation()
  const showFab = FAB_PATHS.has(location.pathname)

  return (
    <div className="relative mx-auto flex h-screen max-w-md flex-col overflow-hidden bg-surface">
      <main className="min-h-0 flex-1 overflow-auto">
        <Outlet />
      </main>

      {showFab && (
        <Link
          to="/reports/new"
          aria-label={t('report.submit.newAction')}
          className="absolute bottom-20 right-4 grid h-14 w-14 place-items-center rounded-pill bg-accent text-2xl text-fg-inverse shadow-fab"
        >
          ＋
        </Link>
      )}

      <nav className="flex border-t border-border bg-surface-raised/95 backdrop-blur">
        {TABS.map((tab) => (
          <NavLink key={tab.to} to={tab.to} end={tab.end} className={tabClass}>
            <span className="text-lg leading-none">{tab.icon}</span>
            {t(tab.labelKey)}
          </NavLink>
        ))}
      </nav>
    </div>
  )
}
