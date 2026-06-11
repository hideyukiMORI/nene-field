import { zodResolver } from '@hookform/resolvers/zod'
import { useForm, type FieldError } from 'react-hook-form'
import { z } from 'zod'
import {
  ASSIGNABLE_USER_ROLES,
  type AssignableUserRole,
  type CreateUserInput,
} from '@/entities/user'
import { useTranslation } from '@/shared/i18n'
import type { MessageKey } from '@/shared/i18n'
import { Button, Field, InlineAlert, Input, Select, Stack, Text } from '@/shared/ui'

const schema = z.object({
  name: z.string().min(1).max(100),
  email: z.email(),
  role: z.enum(['submitter', 'approver', 'admin']),
  password: z.string().min(8),
})

type FormValues = z.infer<typeof schema>

const roleLabelKey: Record<AssignableUserRole, MessageKey> = {
  submitter: 'user.role.submitter',
  approver: 'user.role.approver',
  admin: 'user.role.admin',
}

interface UserCreateFormProps {
  onSubmit: (input: CreateUserInput) => void
  isPending: boolean
  errorKey: MessageKey | null
}

export function UserCreateForm({ onSubmit, isPending, errorKey }: UserCreateFormProps) {
  const { t } = useTranslation()
  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: { name: '', email: '', role: 'submitter', password: '' },
  })

  const messageFor = (error: FieldError | undefined, tooSmall: MessageKey): string | undefined => {
    if (error === undefined) return undefined
    if (error.type === 'too_big') return t('error.validation.too_long')
    return t(tooSmall)
  }

  return (
    <main className="mx-auto w-full max-w-md p-4">
      <Stack gap="md">
        <Text variant="title" as="h2">
          {t('user.form.createTitle')}
        </Text>
        {errorKey !== null && <InlineAlert variant="error">{t(errorKey)}</InlineAlert>}
        <form
          onSubmit={(event) => {
            void handleSubmit((values) => {
              onSubmit(values)
            })(event)
          }}
          noValidate
        >
          <Stack gap="md">
            <Field
              label={t('user.form.name')}
              htmlFor="user-name"
              error={messageFor(errors.name, 'error.validation.required')}
            >
              <Input id="user-name" {...register('name')} />
            </Field>
            <Field
              label={t('user.form.email')}
              htmlFor="user-email"
              error={errors.email !== undefined ? t('error.validation.invalid_format') : undefined}
            >
              <Input id="user-email" type="email" autoComplete="off" {...register('email')} />
            </Field>
            <Field label={t('user.form.role')} htmlFor="user-role">
              <Select id="user-role" {...register('role')}>
                {ASSIGNABLE_USER_ROLES.map((role) => (
                  <option key={role} value={role}>
                    {t(roleLabelKey[role])}
                  </option>
                ))}
              </Select>
            </Field>
            <Field
              label={t('user.form.password')}
              htmlFor="user-password"
              error={messageFor(errors.password, 'error.validation.too_short')}
            >
              <Input
                id="user-password"
                type="password"
                autoComplete="new-password"
                {...register('password')}
              />
            </Field>
            <Button type="submit" disabled={isPending}>
              {t('common.actions.create')}
            </Button>
          </Stack>
        </form>
      </Stack>
    </main>
  )
}
