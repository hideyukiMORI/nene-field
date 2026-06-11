import { zodResolver } from '@hookform/resolvers/zod'
import { useForm, type FieldError } from 'react-hook-form'
import { z } from 'zod'
import {
  ASSIGNABLE_USER_ROLES,
  type AssignableUserRole,
  type User,
  type UserRole,
} from '@/entities/user'
import { useTranslation } from '@/shared/i18n'
import type { MessageKey } from '@/shared/i18n'
import { Button, Field, InlineAlert, Input, Select, Stack, Text } from '@/shared/ui'
import type { EditUserPayload } from '../hooks/use-edit-user'

const schema = z.object({
  name: z.string().min(1).max(100),
  role: z.enum(['submitter', 'approver', 'admin']),
  isActive: z.boolean(),
})

type FormValues = z.infer<typeof schema>

const roleLabelKey: Record<AssignableUserRole, MessageKey> = {
  submitter: 'user.role.submitter',
  approver: 'user.role.approver',
  admin: 'user.role.admin',
}

function toAssignable(role: UserRole): AssignableUserRole {
  return role === 'submitter' || role === 'approver' || role === 'admin' ? role : 'admin'
}

interface UserEditFormProps {
  user: User
  onSubmit: (payload: EditUserPayload) => void
  isPending: boolean
  errorKey: MessageKey | null
}

export function UserEditForm({ user, onSubmit, isPending, errorKey }: UserEditFormProps) {
  const { t } = useTranslation()
  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: { name: user.name, role: toAssignable(user.role), isActive: user.isActive },
  })

  const nameError = (error: FieldError | undefined): string | undefined => {
    if (error === undefined) return undefined
    return t(error.type === 'too_big' ? 'error.validation.too_long' : 'error.validation.required')
  }

  return (
    <main className="mx-auto w-full max-w-md p-4">
      <Stack gap="md">
        <Text variant="title" as="h2">
          {t('user.form.editTitle')}
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
            <Field label={t('user.form.name')} htmlFor="user-name" error={nameError(errors.name)}>
              <Input id="user-name" {...register('name')} />
            </Field>
            <Field label={t('user.form.email')} htmlFor="user-email">
              <Input id="user-email" value={user.email} readOnly disabled />
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
            <label className="flex items-center gap-2 text-sm text-fg">
              <input type="checkbox" {...register('isActive')} />
              {t('user.form.isActive')}
            </label>
            <Button type="submit" disabled={isPending}>
              {t('common.actions.save')}
            </Button>
          </Stack>
        </form>
      </Stack>
    </main>
  )
}
