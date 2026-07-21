import { zodResolver } from '@hookform/resolvers/zod'
import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import { sessionExpired } from '@/entities/auth'
import { useTranslation } from '@/shared/i18n'
import { Field, InlineAlert, Input } from '@/shared/ui'
import { useSignIn } from '../model/use-sign-in'

const schema = z.object({
  email: z.string().min(1),
  password: z.string().min(1),
})

type FormValues = z.infer<typeof schema>

type Role = 'submitter' | 'manager'

// Exact brand colours from the design handoff (NeNe Field Login.dc.html). These
// are one-off login-surface values, applied inline rather than as theme tokens.
const PAGE_BG = 'radial-gradient(120% 80% at 20% -10%, #16586e 0%, #0c3a49 55%, #0a2d39 100%)'
const BRAND_BG = 'linear-gradient(160deg, #0e4a5e 0%, #0b3340 100%)'

/**
 * Login (design handoff §5.1 / §2.3): teal radial background, 980px split card —
 * brand panel (1.1fr) + form (1fr). The role toggle mirrors the prototype; the
 * actual role is resolved by the API after sign-in, which drives the shell.
 */
export function SignIn() {
  const { t, locale, setLocale } = useTranslation()
  const { signIn, isPending, errorKey } = useSignIn()
  const [role, setRole] = useState<Role>('submitter')
  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: { email: '', password: '' },
  })

  const onSubmit = (values: FormValues): void => {
    signIn(values)
  }

  const pill = (active: boolean): string =>
    [
      'flex-1 rounded-x-pill py-2 text-center text-sm font-bold transition-colors',
      active ? 'bg-surface-raised text-on-accent shadow-x-card' : 'text-text-muted',
    ].join(' ')

  return (
    <main
      className="grid min-h-screen place-items-center px-4 py-7"
      style={{ background: PAGE_BG }}
    >
      <button
        type="button"
        onClick={() => {
          setLocale(locale === 'ja' ? 'en' : 'ja')
        }}
        className="fixed right-5 top-5 rounded-x-pill border border-white/25 px-3 py-1.5 text-xs font-semibold text-text-inverse/90 hover:bg-white/10"
      >
        {locale === 'ja' ? 'EN' : '日本語'}
      </button>

      <div
        className="grid w-full overflow-hidden rounded-3xl shadow-x-modal animate-nfup max-md:grid-cols-1"
        style={{ maxWidth: 980, gridTemplateColumns: '1.1fr 1fr' }}
      >
        {/* ── brand panel ─────────────────────────────────────────── */}
        <div
          className="hidden flex-col justify-between p-9 text-text-inverse md:flex"
          style={{ background: BRAND_BG }}
        >
          <div className="flex items-center gap-3">
            <span
              className="grid h-11 w-11 place-items-center rounded-xl text-xl font-bold text-text-inverse"
              style={{ background: 'linear-gradient(150deg, #3fb6c4, #1488ad)' }}
            >
              N
            </span>
            <div>
              <div className="text-lg font-bold tracking-wide">{t('common.app.name')}</div>
              <div className="text-xs" style={{ color: '#9fc6d2' }}>
                {t('auth.login.brandSubtitle')}
              </div>
            </div>
          </div>

          <div className="py-2">
            <h1 className="font-bold leading-relaxed" style={{ fontSize: '26px' }}>
              {t('auth.login.tagline')}
            </h1>
            <p className="mt-4 leading-loose" style={{ color: '#bfe2ee', fontSize: '13.5px' }}>
              {t('auth.login.lead')}
            </p>
          </div>

          <div className="grid grid-cols-3 gap-3">
            <Metric
              value={t('auth.login.metric.speedValue')}
              label={t('auth.login.metric.speedLabel')}
            />
            <Metric
              value={t('auth.login.metric.langValue')}
              label={t('auth.login.metric.langLabel')}
            />
            <Metric value={t('auth.login.metric.aiValue')} label={t('auth.login.metric.aiLabel')} />
          </div>
        </div>

        {/* ── form panel ──────────────────────────────────────────── */}
        <div className="bg-surface-raised p-8 sm:p-10">
          <div className="mb-6 flex items-center gap-2.5 md:hidden">
            <span
              className="grid h-9 w-9 place-items-center rounded-xl text-base font-bold text-text-inverse"
              style={{ background: 'linear-gradient(150deg, #3fb6c4, #1488ad)' }}
            >
              N
            </span>
            <span className="text-lg font-extrabold">{t('common.app.name')}</span>
          </div>

          <h2 className="font-bold text-text-primary" style={{ fontSize: '22px' }}>
            {t('auth.login.title')}
          </h2>
          <p className="mt-1 mb-6 text-sm text-text-muted">{t('auth.login.subtitle')}</p>

          {sessionExpired() && (
            <div className="mb-4">
              <InlineAlert variant="warn">{t('auth.login.sessionExpired')}</InlineAlert>
            </div>
          )}
          {errorKey !== null && (
            <div className="mb-4">
              <InlineAlert variant="error">{t(errorKey)}</InlineAlert>
            </div>
          )}

          <form
            onSubmit={(event) => {
              void handleSubmit(onSubmit)(event)
            }}
            noValidate
          >
            <Field
              label={t('auth.login.email')}
              htmlFor="login-email"
              error={errors.email ? t('error.validation.required') : undefined}
            >
              <Input id="login-email" type="email" autoComplete="username" {...register('email')} />
            </Field>
            <div className="h-4" />
            <Field
              label={t('auth.login.password')}
              htmlFor="login-password"
              error={errors.password ? t('error.validation.required') : undefined}
            >
              <Input
                id="login-password"
                type="password"
                autoComplete="current-password"
                {...register('password')}
              />
            </Field>

            <p className="mt-5 mb-2 text-xs font-bold text-text-primary">
              {t('auth.login.roleLabel')}
            </p>
            <div className="flex gap-1 rounded-x-pill bg-surface-overlay p-1">
              <button
                type="button"
                className={pill(role === 'submitter')}
                onClick={() => {
                  setRole('submitter')
                }}
              >
                {t('auth.login.roleSubmitter')}
              </button>
              <button
                type="button"
                className={pill(role === 'manager')}
                onClick={() => {
                  setRole('manager')
                }}
              >
                {t('auth.login.roleManager')}
              </button>
            </div>
            <p className="mt-2 mb-5 text-xs leading-relaxed text-x-fg-faint-2">
              {t(role === 'submitter' ? 'auth.login.destSubmitter' : 'auth.login.destManager')}
            </p>

            <button
              type="submit"
              disabled={isPending}
              className="flex w-full items-center justify-center gap-2 rounded-x-pill bg-accent py-3.5 font-bold text-text-inverse shadow-x-btn transition-transform active:scale-95 disabled:opacity-50"
              style={{ fontSize: '15px' }}
            >
              {isPending
                ? t('auth.login.submitting')
                : `${t(role === 'submitter' ? 'auth.login.loginAsSubmitter' : 'auth.login.loginAsManager')} →`}
            </button>

            <p className="mt-4 text-center text-xs font-semibold text-on-accent">
              {t('auth.login.forgot')}
            </p>
          </form>

          <p className="mt-6 border-t border-border pt-4 text-xs leading-relaxed text-x-fg-faint-2">
            {t('auth.login.demoNote')}
          </p>
        </div>
      </div>
    </main>
  )
}

function Metric({ value, label }: { value: string; label: string }) {
  return (
    <div>
      <div className="font-bold tabular-nums" style={{ fontSize: '22px' }}>
        {value}
      </div>
      <div className="text-xs" style={{ color: '#9fc6d2' }}>
        {label}
      </div>
    </div>
  )
}
