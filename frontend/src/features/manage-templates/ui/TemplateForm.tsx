import { zodResolver } from '@hookform/resolvers/zod'
import {
  useFieldArray,
  useForm,
  useWatch,
  type Control,
  type FieldErrors,
  type UseFormRegister,
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
import { Button, Field, InlineAlert, Input, Select, Stack, Text, Textarea } from '@/shared/ui'

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

function splitOptions(raw: string): string[] {
  return raw
    .split(',')
    .map((option) => option.trim())
    .filter((option) => option !== '')
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
    fields: template.fields.map((field) => ({
      name: field.name,
      label: field.label,
      type: field.type,
      required: field.required,
      options: field.options.join(', '),
    })),
  }
}

interface FieldRowProps {
  index: number
  register: UseFormRegister<FormValues>
  control: Control<FormValues>
  errors: FieldErrors<FormValues>
  isPending: boolean
  onRemove: () => void
}

function FieldRow({ index, register, control, errors, isPending, onRemove }: FieldRowProps) {
  const { t } = useTranslation()
  const type = useWatch({ control, name: `fields.${index}.type` })
  const rowErrors = errors.fields?.[index]
  const requiredText = (present: boolean): string | undefined =>
    present ? t('error.validation.required') : undefined

  return (
    <fieldset className="border border-border bg-surface-raised p-3">
      <Stack gap="sm">
        <div className="grid grid-cols-2 gap-2">
          <Field
            label={t('template.field.name')}
            htmlFor={`field-${index}-name`}
            error={requiredText(rowErrors?.name !== undefined)}
          >
            <Input id={`field-${index}-name`} {...register(`fields.${index}.name`)} />
          </Field>
          <Field
            label={t('template.field.label')}
            htmlFor={`field-${index}-label`}
            error={requiredText(rowErrors?.label !== undefined)}
          >
            <Input id={`field-${index}-label`} {...register(`fields.${index}.label`)} />
          </Field>
        </div>
        <div className="grid grid-cols-2 gap-2">
          <Field label={t('template.field.type')} htmlFor={`field-${index}-type`}>
            <Select id={`field-${index}-type`} {...register(`fields.${index}.type`)}>
              {TEMPLATE_FIELD_TYPES.map((fieldType) => (
                <option key={fieldType} value={fieldType}>
                  {t(TYPE_LABEL_KEY[fieldType])}
                </option>
              ))}
            </Select>
          </Field>
          <label className="flex items-end gap-2 pb-2 text-sm text-fg">
            <input type="checkbox" {...register(`fields.${index}.required`)} />
            {t('template.field.required')}
          </label>
        </div>
        {type === 'select' && (
          <Field
            label={t('template.field.options')}
            htmlFor={`field-${index}-options`}
            error={requiredText(rowErrors?.options !== undefined)}
          >
            <Input
              id={`field-${index}-options`}
              placeholder={t('template.field.optionsHint')}
              {...register(`fields.${index}.options`)}
            />
          </Field>
        )}
        <div>
          <Button variant="secondary" onClick={onRemove} disabled={isPending}>
            {t('template.form.removeField')}
          </Button>
        </div>
      </Stack>
    </fieldset>
  )
}

interface TemplateFormProps {
  mode: 'create' | 'edit'
  initialTemplate?: Template | undefined
  onSave: (input: CreateTemplateInput) => void
  isPending: boolean
  errorKey: MessageKey | null
}

export function TemplateForm({
  mode,
  initialTemplate,
  onSave,
  isPending,
  errorKey,
}: TemplateFormProps) {
  const { t } = useTranslation()
  const {
    register,
    handleSubmit,
    control,
    formState: { errors },
  } = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: toFormValues(initialTemplate),
  })
  const { fields, append, remove } = useFieldArray({ control, name: 'fields' })

  const onSubmit = (values: FormValues): void => {
    onSave({
      name: values.name,
      description: values.description.trim() === '' ? null : values.description,
      isDefault: values.isDefault,
      fields: values.fields.map((field) => ({
        name: field.name,
        label: field.label,
        type: field.type,
        required: field.required,
        options: field.type === 'select' ? splitOptions(field.options) : [],
      })),
    })
  }

  return (
    <main className="mx-auto w-full max-w-2xl p-4">
      <Stack gap="md">
        <Text variant="title" as="h2">
          {t(mode === 'edit' ? 'template.form.editTitle' : 'template.form.createTitle')}
        </Text>

        {errorKey !== null && <InlineAlert variant="error">{t(errorKey)}</InlineAlert>}

        <form
          onSubmit={(event) => {
            void handleSubmit(onSubmit)(event)
          }}
          noValidate
        >
          <Stack gap="md">
            <Field
              label={t('template.form.name')}
              htmlFor="template-name"
              error={errors.name !== undefined ? t('error.validation.required') : undefined}
            >
              <Input id="template-name" {...register('name')} />
            </Field>
            <Field label={t('template.form.description')} htmlFor="template-description">
              <Textarea id="template-description" rows={2} {...register('description')} />
            </Field>
            <label className="flex items-center gap-2 text-sm text-fg">
              <input type="checkbox" {...register('isDefault')} />
              {t('template.form.isDefault')}
            </label>

            <Stack gap="sm">
              <Text variant="subtitle">{t('template.form.fields')}</Text>
              {fields.map((field, index) => (
                <FieldRow
                  key={field.id}
                  index={index}
                  register={register}
                  control={control}
                  errors={errors}
                  isPending={isPending}
                  onRemove={() => {
                    remove(index)
                  }}
                />
              ))}

              {fields.length === 0 && (
                <InlineAlert variant="warn">{t('template.form.fieldsRequired')}</InlineAlert>
              )}

              <div>
                <Button
                  variant="secondary"
                  onClick={() => {
                    append({ name: '', label: '', type: 'text', required: false, options: '' })
                  }}
                  disabled={isPending}
                >
                  {t('template.form.addField')}
                </Button>
              </div>
            </Stack>

            <Button type="submit" disabled={isPending}>
              {t('common.actions.save')}
            </Button>
          </Stack>
        </form>
      </Stack>
    </main>
  )
}
