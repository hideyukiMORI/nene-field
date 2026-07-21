import { zodResolver } from '@hookform/resolvers/zod'
import { useState, type ReactNode } from 'react'
import { useForm, useWatch } from 'react-hook-form'
import { z } from 'zod'
import type { Organization } from '@/entities/organization'
import { LOCALES, resolveLocale, useTranslation } from '@/shared/i18n'
import type { MessageKey } from '@/shared/i18n'
import {
  Badge,
  Button,
  Card,
  Field,
  InlineAlert,
  Input,
  Select,
  Toggle,
  useToast,
} from '@/shared/ui'
import type { OrganizationSettingsValues } from '../model/use-organization-settings'

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

function SectionCard({
  title,
  subtitle,
  action,
  children,
}: {
  title: string
  subtitle?: string
  action?: ReactNode
  children: ReactNode
}) {
  return (
    <Card padded={false}>
      <div className="flex items-center gap-3 border-b border-border px-5 py-3.5">
        <div className="min-w-0">
          <h3 className="text-sm font-bold text-text-primary">{title}</h3>
          {subtitle !== undefined && <p className="mt-0.5 text-xs text-text-muted">{subtitle}</p>}
        </div>
        {action !== undefined && <div className="ml-auto flex-none">{action}</div>}
      </div>
      <div className="p-5">{children}</div>
    </Card>
  )
}

export function OrganizationSettingsForm({
  organization,
  onSave,
  isPending,
  isSaved,
  errorKey,
}: OrganizationSettingsFormProps) {
  const { t, locale, setLocale } = useTranslation()
  const toast = useToast()
  const {
    register,
    handleSubmit,
    control,
    setValue,
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
  const aiEnabled = useWatch({ control, name: 'aiSummaryEnabled' })

  // Design sections not yet backed by the settings mutation (kept as local UI).
  const [contactEmail, setContactEmail] = useState('')
  const [timezone, setTimezone] = useState('Asia/Tokyo')
  const [onePerDay, setOnePerDay] = useState(true)
  const [reminderTime, setReminderTime] = useState('17:00')
  const [retentionDays, setRetentionDays] = useState('365')

  const onSubmit = (values: FormValues): void => {
    onSave({
      name: values.name,
      aiSummaryEnabled: values.aiSummaryEnabled,
      notificationEmail: values.notificationEmail.trim() === '' ? null : values.notificationEmail,
      webhookUrl: values.webhookUrl.trim() === '' ? null : values.webhookUrl,
    })
  }

  return (
    <div className="mx-auto w-full max-w-3xl">
      {/* document header (書類): kicker · 23px title · top-right save */}
      <div className="mb-4.5 flex flex-wrap items-end gap-4.5 border-b border-border pb-4.5">
        <div className="min-w-0">
          <p className="text-xs font-bold tracking-wide text-on-accent">{t('settings.kicker')}</p>
          <h2 className="mt-2 text-doc-title font-bold tracking-tight text-text-primary">
            {t('settings.title')}
          </h2>
          <p className="mt-1.5 text-sm text-x-fg-muted-2">{t('settings.subtitle')}</p>
        </div>
        <div className="flex-1" />
        <Button type="submit" form="org-settings-form" disabled={isPending} className="flex-none">
          {t('common.actions.save')}
        </Button>
      </div>

      {isSaved && (
        <InlineAlert variant="success" className="mb-4">
          {t('settings.saved')}
        </InlineAlert>
      )}
      {errorKey !== null && (
        <InlineAlert variant="error" className="mb-4">
          {t(errorKey)}
        </InlineAlert>
      )}

      <form
        id="org-settings-form"
        onSubmit={(event) => {
          void handleSubmit(onSubmit)(event)
        }}
        noValidate
        className="flex flex-col gap-4.5"
      >
        <SectionCard title={t('settings.section.basic')}>
          <div className="flex flex-col gap-4">
            <div className="grid gap-4 sm:grid-cols-2">
              <Field
                label={t('settings.name')}
                htmlFor="org-name"
                error={errors.name !== undefined ? t('error.validation.required') : undefined}
              >
                <Input id="org-name" {...register('name')} />
              </Field>
              <Field label={t('settings.contactEmail')} htmlFor="org-contact-email">
                <Input
                  id="org-contact-email"
                  type="email"
                  value={contactEmail}
                  onChange={(e) => {
                    setContactEmail(e.target.value)
                  }}
                />
              </Field>
            </div>
            <dl className="flex flex-wrap gap-x-8 gap-y-2 text-sm">
              <div className="flex gap-2">
                <dt className="text-text-muted">{t('settings.info.slug')}</dt>
                <dd className="font-mono text-text-primary">{organization.slug}</dd>
              </div>
              <div className="flex gap-2">
                <dt className="text-text-muted">{t('settings.info.status')}</dt>
                <dd>
                  <Badge tone={organization.isActive ? 'approved' : 'neutral'}>
                    {t(organization.isActive ? 'settings.info.active' : 'settings.info.inactive')}
                  </Badge>
                </dd>
              </div>
            </dl>
            <p className="text-xs text-text-faint">{t('settings.info.note')}</p>
          </div>
        </SectionCard>

        <SectionCard
          title={t('settings.section.ai')}
          subtitle={t('settings.ai.subtitle')}
          action={
            <Toggle
              checked={aiEnabled}
              onChange={(next) => {
                setValue('aiSummaryEnabled', next, { shouldDirty: true })
              }}
              label={t('settings.aiSummaryEnabled')}
            />
          }
        >
          <div className="flex flex-col gap-4">
            <div className={aiEnabled ? '' : 'pointer-events-none opacity-50'}>
              <div className="flex flex-col gap-4">
                <Field label={t('settings.ai.apiUrl')} htmlFor="org-ai-url">
                  <Input
                    id="org-ai-url"
                    placeholder="https://api.example.com/v1/summarize"
                    disabled={!aiEnabled}
                  />
                </Field>
                <Field label={t('settings.ai.apiKey')} htmlFor="org-ai-key">
                  <div className="flex gap-2">
                    <Input
                      id="org-ai-key"
                      type="password"
                      placeholder="sk-••••••••"
                      disabled={!aiEnabled}
                    />
                    <Button
                      variant="ghost"
                      disabled={!aiEnabled}
                      className="flex-none whitespace-nowrap"
                      onClick={() => {
                        toast.show(t('settings.ai.tested'))
                      }}
                    >
                      {t('settings.ai.test')}
                    </Button>
                  </div>
                </Field>
              </div>
            </div>
          </div>
        </SectionCard>

        <SectionCard title={t('settings.section.notify')}>
          <div className="flex flex-col gap-4">
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
              <div className="flex gap-2">
                <Input id="org-webhook-url" {...register('webhookUrl')} />
                <Button
                  variant="ghost"
                  className="flex-none whitespace-nowrap"
                  onClick={() => {
                    toast.show(t('settings.notify.tested'))
                  }}
                >
                  {t('settings.notify.test')}
                </Button>
              </div>
            </Field>
          </div>
        </SectionCard>

        <SectionCard title={t('settings.section.locale')}>
          <div className="grid gap-4 sm:grid-cols-2">
            <Field label={t('settings.locale.language')} htmlFor="org-language">
              <Select
                id="org-language"
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
            </Field>
            <Field label={t('settings.locale.timezone')} htmlFor="org-timezone">
              <Select
                id="org-timezone"
                value={timezone}
                onChange={(e) => {
                  setTimezone(e.target.value)
                }}
              >
                <option value="Asia/Tokyo">Asia/Tokyo</option>
                <option value="UTC">UTC</option>
              </Select>
            </Field>
          </div>
        </SectionCard>

        <SectionCard title={t('settings.section.rules')}>
          <div className="flex flex-col gap-4">
            <label className="flex items-center gap-2 text-sm text-text-primary">
              <Toggle
                checked={onePerDay}
                onChange={setOnePerDay}
                label={t('settings.rules.oneReportPerDay')}
              />
              {t('settings.rules.oneReportPerDay')}
            </label>
            <div className="grid gap-4 sm:grid-cols-2">
              <Field label={t('settings.rules.reminderTime')} htmlFor="org-reminder">
                <Input
                  id="org-reminder"
                  type="time"
                  value={reminderTime}
                  onChange={(e) => {
                    setReminderTime(e.target.value)
                  }}
                />
              </Field>
              <Field label={t('settings.rules.retentionDays')} htmlFor="org-retention">
                <Input
                  id="org-retention"
                  type="number"
                  value={retentionDays}
                  onChange={(e) => {
                    setRetentionDays(e.target.value)
                  }}
                />
              </Field>
            </div>
          </div>
        </SectionCard>

        <Card className="border-x-rejected/40">
          <h3 className="text-sm font-bold text-x-rejected">{t('settings.section.danger')}</h3>
          <p className="mt-1 mb-3 text-xs text-text-muted">{t('settings.danger.note')}</p>
          <Button
            variant="danger-ghost"
            onClick={() => {
              if (window.confirm(t('settings.danger.confirm')))
                toast.show(t('settings.danger.done'))
            }}
          >
            {t('settings.danger.deactivate')}
          </Button>
        </Card>
      </form>
    </div>
  )
}
