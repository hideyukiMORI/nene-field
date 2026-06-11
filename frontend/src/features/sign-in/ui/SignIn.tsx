import { zodResolver } from '@hookform/resolvers/zod'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import { sessionExpired } from '@/entities/auth'
import { useTranslation } from '@/shared/i18n'
import { Button, Field, InlineAlert, Input, Stack, Text } from '@/shared/ui'
import { useSignIn } from '../hooks/use-sign-in'

const schema = z.object({
  email: z.string().min(1),
  password: z.string().min(1),
})

type FormValues = z.infer<typeof schema>

export function SignIn() {
  const { t } = useTranslation()
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
    <main className="mx-auto flex min-h-screen w-full max-w-sm flex-col justify-center gap-6 p-6">
      <Stack gap="sm">
        <Text variant="title" as="h1">
          {t('auth.login.title')}
        </Text>
        <Text variant="subtitle">{t('auth.login.subtitle')}</Text>
      </Stack>

      {sessionExpired() && (
        <InlineAlert variant="warn">{t('auth.login.sessionExpired')}</InlineAlert>
      )}
      {errorKey !== null && <InlineAlert variant="error">{t(errorKey)}</InlineAlert>}

      <form
        onSubmit={(event) => {
          void handleSubmit(onSubmit)(event)
        }}
        noValidate
      >
        <Stack gap="md">
          <Field
            label={t('auth.login.email')}
            htmlFor="login-email"
            error={errors.email ? t('error.validation.required') : undefined}
          >
            <Input id="login-email" type="email" autoComplete="username" {...register('email')} />
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
          <Button type="submit" disabled={isPending}>
            {isPending ? t('auth.login.submitting') : t('auth.login.button')}
          </Button>
        </Stack>
      </form>
    </main>
  )
}
