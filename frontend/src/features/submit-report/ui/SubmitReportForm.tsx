import { zodResolver } from '@hookform/resolvers/zod'
import { useRef } from 'react'
import { useForm, type FieldError } from 'react-hook-form'
import { z } from 'zod'
import type { CreateReportInput } from '@/entities/report'
import { useTemplateListQuery } from '@/entities/report-template'
import { useTranslation } from '@/shared/i18n'
import type { MessageKey } from '@/shared/i18n'
import { Button, Field, InlineAlert, Input, Select, Stack, Text, Textarea } from '@/shared/ui'
import { useSubmitReport } from '../hooks/use-submit-report'

const schema = z.object({
  title: z.string().min(1).max(200),
  workDate: z.string().min(1),
  body: z.string().min(1).max(10000),
  projectCode: z.string().max(100).optional(),
  tags: z.string().optional(),
  templateId: z.string().optional(),
})

type FormValues = z.infer<typeof schema>

function todayIso(): string {
  return new Date().toISOString().slice(0, 10)
}

function parseTags(raw: string | undefined): string[] {
  if (raw === undefined) return []
  return raw
    .split(',')
    .map((tag) => tag.trim())
    .filter((tag) => tag !== '')
}

export function SubmitReportForm({ onDone }: { onDone: (reportId: string) => void }) {
  const { t } = useTranslation()
  const { saveDraft, submit, isPending, errorKey } = useSubmitReport(onDone)
  const templates = useTemplateListQuery()
  const modeRef = useRef<'draft' | 'submit'>('submit')
  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: {
      title: '',
      workDate: todayIso(),
      body: '',
      projectCode: '',
      tags: '',
      templateId: '',
    },
  })

  const errorText = (error: FieldError | undefined): string | undefined => {
    if (error === undefined) return undefined
    const key: MessageKey =
      error.type === 'too_big' ? 'error.validation.too_long' : 'error.validation.required'
    return t(key)
  }

  const onSubmit = (values: FormValues): void => {
    const input: CreateReportInput = {
      title: values.title,
      body: values.body,
      workDate: values.workDate,
      tags: parseTags(values.tags),
      projectCode:
        values.projectCode !== undefined && values.projectCode !== '' ? values.projectCode : null,
      templateId:
        values.templateId !== undefined && values.templateId !== '' ? values.templateId : null,
    }
    if (modeRef.current === 'draft') {
      saveDraft(input)
    } else {
      submit(input)
    }
  }

  return (
    <main className="mx-auto w-full max-w-md p-4">
      <Stack gap="md">
        <Stack gap="sm">
          <Text variant="title" as="h2">
            {t('report.submit.title')}
          </Text>
          <Text variant="subtitle">{t('report.submit.subtitle')}</Text>
        </Stack>

        {errorKey !== null && <InlineAlert variant="error">{t(errorKey)}</InlineAlert>}

        <form
          onSubmit={(event) => {
            void handleSubmit(onSubmit)(event)
          }}
          noValidate
        >
          <Stack gap="md">
            <Field
              label={t('report.submit.fieldTitle')}
              htmlFor="report-title"
              error={errorText(errors.title)}
            >
              <Input id="report-title" {...register('title')} />
            </Field>
            <Field
              label={t('report.submit.workDate')}
              htmlFor="report-work-date"
              error={errorText(errors.workDate)}
            >
              <Input id="report-work-date" type="date" {...register('workDate')} />
            </Field>
            <Field
              label={t('report.submit.body')}
              htmlFor="report-body"
              error={errorText(errors.body)}
            >
              <Textarea id="report-body" rows={6} {...register('body')} />
            </Field>
            <Field label={t('report.submit.projectCode')} htmlFor="report-project">
              <Input id="report-project" {...register('projectCode')} />
            </Field>
            <Field label={t('report.submit.tags')} htmlFor="report-tags">
              <Input
                id="report-tags"
                placeholder={t('report.submit.tagsHint')}
                {...register('tags')}
              />
            </Field>
            <Field label={t('report.submit.template')} htmlFor="report-template">
              <Select id="report-template" {...register('templateId')}>
                <option value="">{t('report.submit.templateNone')}</option>
                {(templates.data ?? []).map((template) => (
                  <option key={template.id} value={template.id}>
                    {template.name}
                  </option>
                ))}
              </Select>
            </Field>

            <div className="flex gap-2">
              <Button
                type="submit"
                onClick={() => {
                  modeRef.current = 'submit'
                }}
                disabled={isPending}
              >
                {isPending ? t('report.submit.saving') : t('report.submit.submit')}
              </Button>
              <Button
                type="submit"
                variant="secondary"
                onClick={() => {
                  modeRef.current = 'draft'
                }}
                disabled={isPending}
              >
                {t('report.submit.saveDraft')}
              </Button>
            </div>
          </Stack>
        </form>
      </Stack>
    </main>
  )
}
