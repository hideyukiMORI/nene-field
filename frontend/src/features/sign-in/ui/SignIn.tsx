import { zodResolver } from '@hookform/resolvers/zod'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import { sessionExpired } from '@/entities/auth'
import { useTranslation } from '@/shared/i18n'
import { Button, Chip, Field, InlineAlert, Input } from '@/shared/ui'
import { useSignIn } from '../hooks/use-sign-in'

const schema = z.object({
  email: z.string().min(1),
  password: z.string().min(1),
})

type FormValues = z.infer<typeof schema>

/**
 * Login (design handoff §5.1 / §2.3): teal radial background with a split card —
 * brand panel + form. Role is server-authoritative, so the prototype's manual
 * role toggle is intentionally omitted; post-login routing is by the resolved role.
 */
export function SignIn() {
  const { t, locale, setLocale } = useTranslation()
  const { signIn, isPending, errorKey } = useSignIn()
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

  return (
    <main className="grid min-h-screen place-items-center bg-gradient-to-br from-accent-deep to-accent-deep-2 p-5">
      <button
        type="button"
        onClick={() => {
          setLocale(locale === 'ja' ? 'en' : 'ja')
        }}
        className="fixed right-5 top-5 rounded-pill border border-white/25 px-3 py-1.5 text-xs font-semibold text-fg-inverse/90 hover:bg-white/10"
      >
        {locale === 'ja' ? 'EN' : '日本語'}
      </button>

      <div className="grid w-full max-w-5xl overflow-hidden rounded-3xl shadow-modal md:grid-cols-2">
        {/* ── brand panel ─────────────────────────────────────────── */}
        <div className="hidden flex-col justify-between bg-gradient-to-br from-accent-deep to-accent-deep-2 p-10 text-fg-inverse md:flex">
          <div className="flex items-center gap-3">
            <span className="grid h-11 w-11 place-items-center rounded-input bg-gradient-to-br from-accent to-accent-deep text-xl font-extrabold">
              N
            </span>
            <span className="text-xl font-extrabold tracking-wide">{t('common.app.name')}</span>
          </div>
          <div className="py-10">
            <h1 className="text-2xl font-bold leading-snug">{t('auth.login.tagline')}</h1>
            <p className="mt-3 text-sm leading-relaxed text-fg-inverse/75">
              {t('auth.login.lead')}
            </p>
          </div>
          <div className="flex flex-wrap gap-2">
            <span className="rounded-pill bg-white/12 px-3 py-1.5 text-xs font-semibold">
              ⚡ {t('auth.login.point.speed')}
            </span>
            <span className="rounded-pill bg-white/12 px-3 py-1.5 text-xs font-semibold">
              🌐 {t('auth.login.point.bilingual')}
            </span>
            <span className="rounded-pill bg-white/12 px-3 py-1.5 text-xs font-semibold">
              ✦ {t('auth.login.point.ai')}
            </span>
          </div>
        </div>

        {/* ── form panel ──────────────────────────────────────────── */}
        <div className="bg-surface-raised p-8 sm:p-10">
          <div className="mb-6 flex items-center gap-2.5 md:hidden">
            <span className="grid h-9 w-9 place-items-center rounded-input bg-gradient-to-br from-accent to-accent-deep text-base font-extrabold text-fg-inverse">
              N
            </span>
            <span className="text-lg font-extrabold">{t('common.app.name')}</span>
          </div>

          <h2 className="text-xl font-bold text-fg">{t('auth.login.title')}</h2>
          <p className="mt-1 mb-6 text-sm text-fg-muted">{t('auth.login.subtitle')}</p>

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
            <div className="flex flex-col gap-4">
              <Field
                label={t('auth.login.email')}
                htmlFor="login-email"
                error={errors.email ? t('error.validation.required') : undefined}
              >
                <Input
                  id="login-email"
                  type="email"
                  autoComplete="username"
                  {...register('email')}
                />
              </Field>
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
              <Button type="submit" size="lg" disabled={isPending} className="mt-1 w-full">
                {isPending ? t('auth.login.submitting') : t('auth.login.button')}
              </Button>
            </div>
          </form>

          <div className="mt-5 flex items-center justify-center">
            <Chip>single / demo</Chip>
          </div>
        </div>
      </div>
    </main>
  )
}
