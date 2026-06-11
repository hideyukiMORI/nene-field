import { zodResolver } from '@hookform/resolvers/zod'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import type { Organization } from '@/entities/organization'
import { useTranslation } from '@/shared/i18n'
import type { MessageKey } from '@/shared/i18n'
import { Button, Field, InlineAlert, Input, Stack, Text } from '@/shared/ui'
import type { OrganizationSettingsValues } from '../hooks/use-organization-settings'

const EMAIL_RE = /^[^@\s]+@[^@\s]+\.[^@\s]+$/

const schema = z.object({
  name: z.string().min(1).max(100),
  aiSummaryEnabled: z.boolean(),
  notificationEmail: z.string().refine((value) => value === '' || EMAIL_RE.test(value), {
    message: 'invalid',
  }),
  webhookUrl: z.string(),
})

type FormValues = z.infer<typeof schema>

interface OrganizationSettingsFormProps {
  organization: Organization
  onSave: (values: OrganizationSettingsValues) => void
  isPending: boolean
  isSaved: boolean
  errorKey: MessageKey | null
}

export function OrganizationSettingsForm({
  organization,
  onSave,
  isPending,
  isSaved,
  errorKey,
}: OrganizationSettingsFormProps) {
  const { t } = useTranslation()
  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: {
      name: organization.name,
      aiSummaryEnabled: organization.aiSummaryEnabled,
      notificationEmail: organization.notificationEmail ?? '',
      webhookUrl: organization.webhookUrl ?? '',
    },
  })

  const onSubmit = (values: FormValues): void => {
    onSave({
      name: values.name,
      aiSummaryEnabled: values.aiSummaryEnabled,
      notificationEmail: values.notificationEmail.trim() === '' ? null : values.notificationEmail,
      webhookUrl: values.webhookUrl.trim() === '' ? null : values.webhookUrl,
    })
  }

  return (
    <Stack gap="md">
      <Stack gap="sm">
        <Text variant="title" as="h2">
          {t('settings.title')}
        </Text>
        <Text variant="subtitle">{t('settings.subtitle')}</Text>
      </Stack>

      {isSaved && <InlineAlert variant="success">{t('settings.saved')}</InlineAlert>}
      {errorKey !== null && <InlineAlert variant="error">{t(errorKey)}</InlineAlert>}

      <form
        onSubmit={(event) => {
          void handleSubmit(onSubmit)(event)
        }}
        noValidate
      >
        <Stack gap="md">
          <Field
            label={t('settings.name')}
            htmlFor="org-name"
            error={errors.name !== undefined ? t('error.validation.required') : undefined}
          >
            <Input id="org-name" {...register('name')} />
          </Field>
          <label className="flex items-center gap-2 text-sm text-fg">
            <input type="checkbox" {...register('aiSummaryEnabled')} />
            {t('settings.aiSummaryEnabled')}
          </label>
          <Field
            label={t('settings.notificationEmail')}
            htmlFor="org-notification-email"
            error={
              errors.notificationEmail !== undefined
                ? t('error.validation.invalid_format')
                : undefined
            }
          >
            <Input id="org-notification-email" type="email" {...register('notificationEmail')} />
          </Field>
          <Field label={t('settings.webhookUrl')} htmlFor="org-webhook-url">
            <Input id="org-webhook-url" {...register('webhookUrl')} />
          </Field>
          <Button type="submit" disabled={isPending}>
            {t('common.actions.save')}
          </Button>
        </Stack>
      </form>

      <div className="border border-border bg-surface-raised p-4">
        <Stack gap="sm">
          <Text variant="subtitle">{t('settings.info.title')}</Text>
          <dl className="grid grid-cols-2 gap-2 text-sm">
            <Info label={t('settings.info.slug')} value={organization.slug} />
            <Info
              label={t('settings.info.customDomain')}
              value={organization.customDomain ?? t('settings.info.none')}
            />
            <Info
              label={t('settings.info.status')}
              value={t(organization.isActive ? 'settings.info.active' : 'settings.info.inactive')}
            />
          </dl>
          <Text variant="muted">{t('settings.info.note')}</Text>
        </Stack>
      </div>
    </Stack>
  )
}

function Info({ label, value }: { label: string; value: string }) {
  return (
    <div className="flex flex-col gap-0.5">
      <dt className="text-fg-muted">{label}</dt>
      <dd className="text-fg">{value}</dd>
    </div>
  )
}
