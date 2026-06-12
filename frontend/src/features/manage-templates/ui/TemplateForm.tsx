import { zodResolver } from '@hookform/resolvers/zod'
import {
  useFieldArray,
  useForm,
  useWatch,
  type Control,
  type FieldErrors,
  type UseFormRegister,
  type UseFormSetValue,
} from 'react-hook-form'
import { z } from 'zod'
import {
  TEMPLATE_FIELD_TYPES,
  type CreateTemplateInput,
  type Template,
  type TemplateFieldType,
} from '@/entities/report-template'
import { useTranslation } from '@/shared/i18n'
import type { MessageKey } from '@/shared/i18n'
import { Button, Chip, Field, InlineAlert, Input, Select, Textarea, Toggle } from '@/shared/ui'

const fieldSchema = z
  .object({
    name: z.string().min(1),
    label: z.string().min(1),
    type: z.enum(['text', 'textarea', 'number', 'checkbox', 'date', 'select']),
    required: z.boolean(),
    options: z.string(),
  })
  .superRefine((value, ctx) => {
    if (value.type === 'select' && value.options.trim() === '') {
      ctx.addIssue({ code: 'custom', path: ['options'], message: 'required' })
    }
  })

const schema = z.object({
  name: z.string().min(1).max(100),
  description: z.string(),
  isDefault: z.boolean(),
  fields: z.array(fieldSchema).min(1),
})

type FormValues = z.infer<typeof schema>

const TYPE_LABEL_KEY: Record<TemplateFieldType, MessageKey> = {
  text: 'template.fieldType.text',
  textarea: 'template.fieldType.textarea',
  number: 'template.fieldType.number',
  checkbox: 'template.fieldType.checkbox',
  date: 'template.fieldType.date',
  select: 'template.fieldType.select',
}

function nextType(type: TemplateFieldType): TemplateFieldType {
  const i = TEMPLATE_FIELD_TYPES.indexOf(type)
  return TEMPLATE_FIELD_TYPES[(i + 1) % TEMPLATE_FIELD_TYPES.length]
}

function splitOptions(raw: string): string[] {
  return raw
    .split(',')
    .map((o) => o.trim())
    .filter((o) => o !== '')
}

function toFormValues(template: Template | undefined): FormValues {
  if (template === undefined) {
    return {
      name: '',
      description: '',
      isDefault: false,
      fields: [{ name: '', label: '', type: 'text', required: false, options: '' }],
    }
  }
  return {
    name: template.name,
    description: template.description ?? '',
    isDefault: template.isDefault,
    fields: template.fields.map((f) => ({
      name: f.name,
      label: f.label,
      type: f.type,
      required: f.required,
      options: f.options.join(', '),
    })),
  }
}

interface FieldEditorProps {
  index: number
  count: number
  control: Control<FormValues>
  register: UseFormRegister<FormValues>
  setValue: UseFormSetValue<FormValues>
  errors: FieldErrors<FormValues>
  isPending: boolean
  onMove: (dir: -1 | 1) => void
  onRemove: () => void
}

function FieldEditor({
  index,
  count,
  control,
  register,
  setValue,
  errors,
  isPending,
  onMove,
  onRemove,
}: FieldEditorProps) {
  const { t } = useTranslation()
  const type = useWatch({ control, name: `fields.${index}.type` })
  const required = useWatch({ control, name: `fields.${index}.required` })
  const rowErrors = errors.fields?.[index]

  return (
    <div className="rounded-card border border-border bg-surface-raised p-4 shadow-card">
      {/* label + reorder */}
      <div className="flex items-start gap-2">
        <input
          aria-label={t('template.field.label')}
          placeholder={t('template.field.label')}
          {...register(`fields.${index}.label`)}
          className="min-w-0 flex-1 rounded-input border border-border-input bg-surface-raised px-3 py-2 text-sm font-semibold text-fg outline-none focus:border-accent focus:ring-2 focus:ring-accent-soft"
        />
        <div className="flex flex-none flex-col">
          <button
            type="button"
            aria-label={t('template.form.moveUp')}
            disabled={index === 0 || isPending}
            onClick={() => {
              onMove(-1)
            }}
            className="grid h-5 w-6 place-items-center text-fg-muted hover:text-fg disabled:opacity-30"
          >
            ▲
          </button>
          <button
            type="button"
            aria-label={t('template.form.moveDown')}
            disabled={index === count - 1 || isPending}
            onClick={() => {
              onMove(1)
            }}
            className="grid h-5 w-6 place-items-center text-fg-muted hover:text-fg disabled:opacity-30"
          >
            ▼
          </button>
        </div>
      </div>

      {/* type pill + required + delete */}
      <div className="mt-2.5 flex flex-wrap items-center gap-2">
        <span className="text-xs text-fg-muted">{t('template.field.type')}</span>
        <Chip
          onClick={() => {
            setValue(`fields.${index}.type`, nextType(type), { shouldDirty: true })
          }}
        >
          {t(TYPE_LABEL_KEY[type])} ⇄
        </Chip>
        <label className="flex items-center gap-1.5 text-sm text-fg">
          <Toggle
            size="sm"
            checked={required}
            onChange={(next) => {
              setValue(`fields.${index}.required`, next, { shouldDirty: true })
            }}
            label={t('template.field.required')}
          />
          {t('template.field.required')}
        </label>
        <button
          type="button"
          disabled={isPending}
          onClick={onRemove}
          className="ml-auto text-sm font-semibold text-rejected hover:underline"
        >
          {t('template.form.removeField')}
        </button>
      </div>

      {/* name + select options (secondary) */}
      <div className="mt-2.5 grid gap-2 sm:grid-cols-2">
        <Field label={t('template.field.name')} htmlFor={`field-${String(index)}-name`}>
          <Input id={`field-${String(index)}-name`} {...register(`fields.${index}.name`)} />
        </Field>
        {type === 'select' && (
          <Field
            label={t('template.field.options')}
            htmlFor={`field-${String(index)}-options`}
            error={rowErrors?.options !== undefined ? t('error.validation.required') : undefined}
          >
            <Input
              id={`field-${String(index)}-options`}
              placeholder={t('template.field.optionsHint')}
              {...register(`fields.${index}.options`)}
            />
          </Field>
        )}
      </div>
    </div>
  )
}

function PreviewPanel({ control }: { control: Control<FormValues> }) {
  const { t } = useTranslation()
  const fields = useWatch({ control, name: 'fields' })
  const name = useWatch({ control, name: 'name' })

  return (
    <div className="overflow-hidden rounded-card border border-border bg-surface-raised shadow-card">
      <div className="bg-accent-deep px-4 py-3 font-bold text-fg-inverse">
        {name.trim() === '' ? t('template.form.preview') : name}
      </div>
      <div className="flex flex-col gap-4 p-4">
        {fields.map((f, i) => {
          const label = f.label.trim() === '' ? t('template.field.label') : f.label
          return (
            <div key={i}>
              {f.type === 'checkbox' ? (
                <label className="flex items-center gap-2 text-sm text-fg">
                  <input type="checkbox" disabled className="accent-accent" />
                  {label}
                  {f.required && <span className="text-rejected">*</span>}
                </label>
              ) : (
                <>
                  <span className="mb-1.5 block text-xs font-semibold text-fg">
                    {label}
                    {f.required && <span className="ml-0.5 text-rejected">*</span>}
                  </span>
                  {f.type === 'textarea' && <Textarea disabled rows={2} />}
                  {f.type === 'select' && (
                    <Select disabled>
                      {splitOptions(f.options).map((o) => (
                        <option key={o}>{o}</option>
                      ))}
                    </Select>
                  )}
                  {(f.type === 'text' || f.type === 'number' || f.type === 'date') && (
                    <Input disabled type={f.type === 'text' ? 'text' : f.type} />
                  )}
                </>
              )}
            </div>
          )
        })}
        <Button disabled className="w-full">
          {t('report.submit.submit')}
        </Button>
      </div>
    </div>
  )
}

interface TemplateFormProps {
  mode: 'create' | 'edit'
  initialTemplate?: Template | undefined
  onSave: (input: CreateTemplateInput) => void
  isPending: boolean
  errorKey: MessageKey | null
}

export function TemplateForm({ initialTemplate, onSave, isPending, errorKey }: TemplateFormProps) {
  const { t } = useTranslation()
  const {
    register,
    handleSubmit,
    control,
    setValue,
    formState: { errors },
  } = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: toFormValues(initialTemplate),
  })
  const { fields, append, remove, move } = useFieldArray({ control, name: 'fields' })
  const isDefault = useWatch({ control, name: 'isDefault' })

  const onSubmit = (values: FormValues): void => {
    onSave({
      name: values.name,
      description: values.description.trim() === '' ? null : values.description,
      isDefault: values.isDefault,
      fields: values.fields.map((f) => ({
        name: f.name,
        label: f.label,
        type: f.type,
        required: f.required,
        options: f.type === 'select' ? splitOptions(f.options) : [],
      })),
    })
  }

  return (
    <form
      onSubmit={(event) => {
        void handleSubmit(onSubmit)(event)
      }}
      noValidate
      className="flex w-full flex-col gap-5"
    >
      {/* header: name + default + save */}
      <div className="flex flex-wrap items-end gap-4">
        <div className="min-w-3xs flex-1">
          <label htmlFor="template-name" className="mb-1 block text-xs font-semibold text-fg-muted">
            {t('template.form.name')}
          </label>
          <input
            id="template-name"
            {...register('name')}
            className="w-full rounded-input border border-border-input bg-surface-raised px-3 py-2.5 text-lg font-bold text-fg outline-none focus:border-accent focus:ring-2 focus:ring-accent-soft"
          />
          {errors.name !== undefined && (
            <p className="mt-1 text-xs text-rejected">{t('error.validation.required')}</p>
          )}
        </div>
        <label className="flex items-center gap-2 pb-2.5 text-sm text-fg">
          <Toggle
            checked={isDefault}
            onChange={(next) => {
              setValue('isDefault', next, { shouldDirty: true })
            }}
            label={t('template.form.isDefault')}
          />
          {t('template.form.isDefault')}
        </label>
        <Button type="submit" disabled={isPending}>
          {t('common.actions.save')}
        </Button>
      </div>

      {errorKey !== null && <InlineAlert variant="error">{t(errorKey)}</InlineAlert>}

      {/* two columns: wider editor + preview */}
      <div className="grid gap-6 lg:grid-cols-3">
        {/* editor (wider) */}
        <div className="flex flex-col gap-3 lg:col-span-2">
          <p className="text-xs font-semibold text-fg-faint">
            {t('template.form.fieldsLabel')} · {t('template.form.reorderHint')}
          </p>
          {fields.map((field, index) => (
            <FieldEditor
              key={field.id}
              index={index}
              count={fields.length}
              control={control}
              register={register}
              setValue={setValue}
              errors={errors}
              isPending={isPending}
              onMove={(dir) => {
                move(index, index + dir)
              }}
              onRemove={() => {
                remove(index)
              }}
            />
          ))}
          {fields.length === 0 && (
            <InlineAlert variant="warn">{t('template.form.fieldsRequired')}</InlineAlert>
          )}
          <button
            type="button"
            disabled={isPending}
            onClick={() => {
              append({ name: '', label: '', type: 'text', required: false, options: '' })
            }}
            className="rounded-card border-2 border-dashed border-border-strong bg-surface-raised py-3 text-sm font-semibold text-accent hover:bg-surface-overlay disabled:opacity-50"
          >
            ＋ {t('template.form.addField')}
          </button>
        </div>

        {/* preview */}
        <div className="flex flex-col gap-3">
          <p className="text-xs font-semibold text-fg-faint">{t('template.form.preview')}</p>
          <PreviewPanel control={control} />
        </div>
      </div>

      <Field label={t('template.form.description')} htmlFor="template-description">
        <Textarea id="template-description" rows={2} {...register('description')} />
      </Field>
    </form>
  )
}
