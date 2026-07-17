import { zodResolver } from '@hookform/resolvers/zod'
import { useRef, useSyncExternalStore } from 'react'
import { useForm, type FieldError } from 'react-hook-form'
import { useNavigate } from 'react-router-dom'
import { z } from 'zod'
import type { CreateReportInput } from '@/entities/report'
import { useTemplateListQuery } from '@/entities/report-template'
import { useTranslation } from '@/shared/i18n'
import type { MessageKey } from '@/shared/i18n'
import { Button, Field, InlineAlert, Input, Select, Textarea } from '@/shared/ui'
import { useSubmitReport } from '../model/use-submit-report'

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

/** Subscribes to the browser online/offline status. */
function useOnline(): boolean {
  return useSyncExternalStore(
    (cb) => {
      window.addEventListener('online', cb)
      window.addEventListener('offline', cb)
      return () => {
        window.removeEventListener('online', cb)
        window.removeEventListener('offline', cb)
      }
    },
    () => navigator.onLine,
    () => true,
  )
}

export function SubmitReportForm({ onDone }: { onDone: (reportId: string) => void }) {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const online = useOnline()
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
    <div className="flex h-full flex-col">
      {/* mobile app bar */}
      <header className="flex items-center gap-3 border-b border-border bg-surface-raised px-4 py-3">
        <button
          type="button"
          aria-label={t('common.actions.back')}
          onClick={() => {
            void navigate(-1)
          }}
          className="text-xl text-fg-muted"
        >
          ✕
        </button>
        <h1 className="flex-1 text-base font-bold text-fg">{t('report.submit.mobileTitle')}</h1>
        <span className="flex items-center gap-1 text-xs text-approved">
          ✓ {t('report.submit.autosave')}
        </span>
      </header>

      <form
        onSubmit={(event) => {
          void handleSubmit(onSubmit)(event)
        }}
        noValidate
        className="flex min-h-0 flex-1 flex-col"
      >
        <div className="flex flex-1 flex-col gap-4 overflow-auto p-4">
          {!online && <InlineAlert variant="warn">{t('report.submit.offline')}</InlineAlert>}
          {errorKey !== null && <InlineAlert variant="error">{t(errorKey)}</InlineAlert>}

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

          <div className="grid grid-cols-2 gap-3">
            <Field
              label={t('report.submit.workDate')}
              htmlFor="report-work-date"
              error={errorText(errors.workDate)}
            >
              <Input id="report-work-date" type="date" {...register('workDate')} />
            </Field>
            <Field label={t('report.submit.projectCode')} htmlFor="report-project">
              <Input id="report-project" {...register('projectCode')} />
            </Field>
          </div>

          <Field
            label={t('report.submit.fieldTitle')}
            htmlFor="report-title"
            error={errorText(errors.title)}
          >
            <Input id="report-title" {...register('title')} />
          </Field>

          <Field
            label={t('report.submit.body')}
            htmlFor="report-body"
            error={errorText(errors.body)}
          >
            <Textarea id="report-body" rows={6} {...register('body')} />
          </Field>

          <Field label={t('report.submit.tags')} htmlFor="report-tags">
            <Input
              id="report-tags"
              placeholder={t('report.submit.tagsHint')}
              {...register('tags')}
            />
          </Field>

          {/* camera-style attachment area */}
          <div>
            <span className="mb-1.5 block text-xs font-semibold text-fg">
              {t('report.submit.photo')}
            </span>
            <button
              type="button"
              className="flex w-full flex-col items-center gap-1 rounded-input border-2 border-dashed border-border-strong bg-surface-overlay py-6 text-fg-muted"
            >
              <span className="text-2xl">📷</span>
              <span className="text-xs">{t('report.submit.photoHint')}</span>
            </button>
          </div>
        </div>

        {/* sticky bottom actions */}
        <div className="flex gap-2.5 border-t border-border bg-surface-raised p-4">
          <Button
            type="submit"
            variant="secondary"
            className="flex-1"
            onClick={() => {
              modeRef.current = 'draft'
            }}
            disabled={isPending}
          >
            {t('report.submit.saveDraft')}
          </Button>
          <Button
            type="submit"
            className="flex-1"
            onClick={() => {
              modeRef.current = 'submit'
            }}
            disabled={isPending}
          >
            {isPending ? t('report.submit.saving') : t('report.submit.submit')}
          </Button>
        </div>
      </form>
    </div>
  )
}
