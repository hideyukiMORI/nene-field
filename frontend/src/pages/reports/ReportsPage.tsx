import { useSyncExternalStore } from 'react'
import { Link } from 'react-router-dom'
import {
  canManageOrganization,
  getCurrentUser,
  signOut,
  subscribeCurrentUser,
} from '@/entities/auth'
import { ListReports } from '@/features/list-reports'
import { LOCALES, resolveLocale, useTranslation } from '@/shared/i18n'
import { Button, Stack, Text } from '@/shared/ui'

function LocaleSwitcher() {
  const { locale, setLocale, t } = useTranslation()
  return (
    <label className="flex items-center gap-2 text-sm text-fg-muted">
      <span>{t('common.language.label')}</span>
      <select
        value={locale}
        onChange={(event) => {
          setLocale(resolveLocale(event.target.value))
        }}
        className="border border-border bg-surface-raised px-2 py-1 text-sm text-fg"
      >
        {LOCALES.map((meta) => (
          <option key={meta.id} value={meta.id}>
            {t(meta.labelKey)}
          </option>
        ))}
      </select>
    </label>
  )
}

export function ReportsPage() {
  const { t } = useTranslation()
  const user = useSyncExternalStore(subscribeCurrentUser, getCurrentUser)

  return (
    <div className="min-h-screen">
      <header className="flex items-center justify-between border-b border-border bg-surface-raised px-6 py-3">
        <Text variant="title" as="h1">
          {t('common.app.name')}
        </Text>
        <div className="flex items-center gap-4">
          <LocaleSwitcher />
          {user !== null && canManageOrganization(user.role) && (
            <Link to="/templates" className="text-sm font-medium text-accent">
              {t('common.nav.templates')}
            </Link>
          )}
          {user !== null && canManageOrganization(user.role) && (
            <Link to="/users" className="text-sm font-medium text-accent">
              {t('common.nav.users')}
            </Link>
          )}
          {user !== null && <span className="text-sm text-fg-muted">{user.name}</span>}
          <Button
            variant="secondary"
            onClick={() => {
              signOut()
            }}
          >
            {t('common.actions.signOut')}
          </Button>
        </div>
      </header>
      <main className="mx-auto w-full max-w-5xl p-6">
        <Stack gap="md">
          <div className="flex items-end justify-between gap-4">
            <Stack gap="sm">
              <Text variant="title" as="h2">
                {t('report.list.title')}
              </Text>
              <Text variant="subtitle">{t('report.list.subtitle')}</Text>
            </Stack>
            <Link
              to="/reports/new"
              className="inline-flex items-center border border-accent bg-accent px-3 py-2 text-sm font-semibold text-fg-inverse"
            >
              {t('report.submit.newAction')}
            </Link>
          </div>
          <ListReports />
        </Stack>
      </main>
    </div>
  )
}
