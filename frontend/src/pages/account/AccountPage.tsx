import { useSyncExternalStore } from 'react'
import {
  canManageOrganization,
  getCurrentUser,
  signOut,
  subscribeCurrentUser,
  type Role,
} from '@/entities/auth'
import { LOCALES, resolveLocale, useTranslation } from '@/shared/i18n'
import type { MessageKey } from '@/shared/i18n'
import { Badge, Button, Card, Select } from '@/shared/ui'

// Keyed by the closed Role union (not `string`) so indexing with `user.role`
// is total and doesn't need an `undefined` fallback under noUncheckedIndexedAccess.
const roleLabelKey: Record<Role, MessageKey> = {
  submitter: 'user.role.submitter',
  approver: 'user.role.approver',
  admin: 'user.role.admin',
  superadmin: 'user.role.superadmin',
}

/** Mobile account / settings tab (design handoff §5.2 settings). */
export function AccountPage() {
  const { t, locale, setLocale } = useTranslation()
  const user = useSyncExternalStore(subscribeCurrentUser, getCurrentUser)
  const canManage = user !== null && canManageOrganization(user.role)

  return (
    <div className="flex flex-col">
      <header className="border-b border-border bg-surface-raised px-4 py-3.5">
        <h1 className="text-base font-bold text-fg">{t('mobile.account.title')}</h1>
      </header>

      <div className="flex flex-col gap-4 p-4">
        <Card className="flex items-center gap-3">
          <span className="grid h-12 w-12 place-items-center rounded-pill bg-accent-soft text-base font-bold text-accent-ink">
            {user?.name.slice(0, 2) ?? '??'}
          </span>
          <div className="min-w-0">
            <p className="truncate font-bold text-fg">{user?.name}</p>
            <p className="truncate text-xs text-fg-muted">{user?.email}</p>
          </div>
          {user !== null && <Badge tone="info">{t(roleLabelKey[user.role])}</Badge>}
        </Card>

        <Card padded={false}>
          <label className="flex items-center justify-between gap-3 p-4">
            <span className="text-sm font-medium text-fg">{t('mobile.account.language')}</span>
            <div className="w-36">
              <Select
                value={locale}
                onChange={(e) => {
                  setLocale(resolveLocale(e.target.value))
                }}
              >
                {LOCALES.map((meta) => (
                  <option key={meta.id} value={meta.id}>
                    {t(meta.labelKey)}
                  </option>
                ))}
              </Select>
            </div>
          </label>
        </Card>

        {canManage && (
          <a
            href="/"
            className="rounded-card border border-border bg-surface-raised px-4 py-3.5 text-sm font-semibold text-accent"
          >
            🖥 {t('mobile.account.openAdmin')}
          </a>
        )}

        <Button variant="secondary" className="w-full" onClick={signOut}>
          {t('common.actions.signOut')}
        </Button>
      </div>
    </div>
  )
}
