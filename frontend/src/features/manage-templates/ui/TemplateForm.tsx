import { zodResolver } from '@hookform/resolvers/zod'
import { useState } from 'react'
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
import { cn } from '@/shared/lib/cn'
import { Button, InlineAlert, Toggle } from '@/shared/ui'

const fieldSchema = z
  .object({
    // Machine name is derived from the label at save (not shown in the editor),
    // so it may be blank in the form.
    name: z.string(),
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
  // (i + 1) % length is always a valid index into the non-empty tuple; the
  // fallback only exists to satisfy noUncheckedIndexedAccess and is never hit.
  return TEMPLATE_FIELD_TYPES[(i + 1) % TEMPLATE_FIELD_TYPES.length] ?? type
}

function splitOptions(raw: string): string[] {
  return raw
    .split(',')
    .map((o) => o.trim())
    .filter((o) => o !== '')
}

/** Machine name from the label (ASCII slug), with a positional fallback. */
function deriveName(label: string, index: number, existing: string): string {
  if (existing.trim() !== '') return existing
  const slug = label
    .trim()
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '_')
    .replace(/^_+|_+$/g, '')
  return slug !== '' ? slug : `field_${String(index + 1)}`
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
  /** Native drag-and-drop reordering via the grip handle (⠿). */
  isDragging: boolean
  isOver: boolean
  onDragStart: () => void
  onDragEnter: () => void
  onDrop: () => void
  onDragEnd: () => void
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
  isDragging,
  isOver,
  onDragStart,
  onDragEnter,
  onDrop,
  onDragEnd,
}: FieldEditorProps) {
  const { t } = useTranslation()
  const type = useWatch({ control, name: `fields.${index}.type` })
  const required = useWatch({ control, name: `fields.${index}.required` })
  const rowErrors = errors.fields?.[index]
  // Native HTML5 DnD is enabled only while the grip (⠿) is held, so text inside
  // the row's inputs stays selectable. The ▲▼ buttons remain for keyboard users.
  const [grabbed, setGrabbed] = useState(false)

  return (
    <div
      draggable={grabbed}
      onDragStart={(event) => {
        event.dataTransfer.effectAllowed = 'move'
        onDragStart()
      }}
      onDragEnter={onDragEnter}
      onDragOver={(event) => {
        event.preventDefault()
      }}
      onDrop={(event) => {
        event.preventDefault()
        setGrabbed(false)
        onDrop()
      }}
      onDragEnd={() => {
        setGrabbed(false)
        onDragEnd()
      }}
      className={cn(
        'rounded-xl border bg-surface-raised px-3.5 py-3 transition-colors',
        isDragging && 'opacity-50',
        isOver && !isDragging ? 'border-accent ring-2 ring-accent-soft' : 'border-border',
      )}
    >
      {/* label + reorder */}
      <div className="flex items-center gap-2.75">
        <span
          aria-hidden
          title={t('template.form.reorderHint')}
          onPointerDown={() => {
            setGrabbed(true)
          }}
          onPointerUp={() => {
            setGrabbed(false)
          }}
          className="cursor-grab touch-none text-base text-border-strong active:cursor-grabbing"
        >
          ⠿
        </span>
        <input
          aria-label={t('template.field.label')}
          placeholder={t('template.field.label')}
          {...register(`fields.${index}.label`)}
          className="min-w-0 flex-1 rounded-lg border border-border bg-surface-raised px-2.75 py-2.25 text-sm font-semibold text-fg outline-none focus:border-accent"
        />
        <div className="flex flex-none flex-col">
          <button
            type="button"
            aria-label={t('template.form.moveUp')}
            disabled={index === 0 || isPending}
            onClick={() => {
              onMove(-1)
            }}
            className="grid h-5 w-6 place-items-center text-micro text-fg-faint hover:text-fg disabled:opacity-30"
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
            className="grid h-5 w-6 place-items-center text-micro text-fg-faint hover:text-fg disabled:opacity-30"
          >
            ▼
          </button>
        </div>
      </div>

      {/* type pill + required + delete */}
      <div className="mt-2.75 flex flex-wrap items-center gap-3 pl-6.75">
        <button
          type="button"
          onClick={() => {
            setValue(`fields.${index}.type`, nextType(type), { shouldDirty: true })
          }}
          title={t('template.field.type')}
          className="inline-flex items-center gap-2 rounded-lg border border-border bg-surface-overlay px-3 py-1.75 text-label text-fg-muted active:bg-surface-overlay"
        >
          <span className="text-micro text-fg-faint-2">{t('template.field.type')}</span>
          <span className="font-semibold text-fg">{t(TYPE_LABEL_KEY[type])}</span>
          <span className="text-fg-faint-2">⇄</span>
        </button>
        <label className="flex items-center gap-2 text-label text-fg-muted">
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
        <div className="flex-1" />
        <button
          type="button"
          disabled={isPending}
          onClick={onRemove}
          className="rounded-lg px-2.25 py-1.25 text-label font-semibold text-rejected hover:bg-rejected-soft disabled:opacity-40"
        >
          {t('template.form.removeField')}
        </button>
      </div>

      {/* select options (only when relevant) */}
      {type === 'select' && (
        <div className="mt-2.75 pl-6.75">
          <input
            aria-label={t('template.field.options')}
            placeholder={t('template.field.optionsHint')}
            {...register(`fields.${index}.options`)}
            className={cn(
              'w-full rounded-lg border bg-surface-raised px-2.75 py-2.25 text-ui text-fg outline-none focus:border-accent',
              rowErrors?.options !== undefined ? 'border-rejected' : 'border-border',
            )}
          />
          {rowErrors?.options !== undefined && (
            <p className="mt-1 text-caption text-rejected">{t('error.validation.required')}</p>
          )}
        </div>
      )}
    </div>
  )
}

function PreviewPanel({ control }: { control: Control<FormValues> }) {
  const { t } = useTranslation()
  const fields = useWatch({ control, name: 'fields' })
  const name = useWatch({ control, name: 'name' })

  return (
    <div className="overflow-hidden rounded-2xl border border-border bg-surface-raised shadow-card">
      <div
        className="px-4 py-3.5 text-base font-bold text-fg-inverse"
        style={{ background: 'linear-gradient(155deg, #1488ad, #0e4a5e)' }}
      >
        {name.trim() === '' ? t('template.form.preview') : name}
      </div>
      <div className="flex flex-col gap-3.5 p-4">
        {fields.map((f, i) => {
          const label = f.label.trim() === '' ? t('template.field.label') : f.label
          return (
            <div key={i}>
              {f.type === 'checkbox' ? (
                <label className="flex items-center gap-2.5 text-ui text-fg-muted">
                  <span className="h-4.5 w-4.5 flex-none rounded border-2 border-border-strong" />
                  {label}
                  {f.required && <span className="text-rejected">＊</span>}
                </label>
              ) : (
                <>
                  <span className="mb-1.5 block text-label font-semibold text-fg-muted">
                    {label}
                    {f.required && <span className="ml-0.5 text-rejected">＊</span>}
                  </span>
                  {f.type === 'select' ? (
                    <div className="flex items-center rounded-lg border border-border-input px-2.75 py-2.5 text-ui text-fg-muted">
                      <span className="flex-1">
                        {splitOptions(f.options)[0] ?? t('template.form.previewPlaceholder')}
                      </span>
                      <span aria-hidden className="text-border-strong">
                        ▾
                      </span>
                    </div>
                  ) : f.type === 'textarea' ? (
                    <div className="min-h-15 rounded-lg border border-border-input px-2.75 py-2.5 text-ui text-fg-faint-2">
                      {t('template.form.previewPlaceholder')}
                    </div>
                  ) : (
                    <div className="rounded-lg border border-border-input px-2.75 py-2.5 text-ui text-fg-faint-2">
                      {t('template.form.previewPlaceholder')}
                    </div>
                  )}
                </>
              )}
            </div>
          )
        })}
        <div className="mt-1.5 rounded-pill bg-accent py-3 text-center text-sm font-bold text-fg-inverse">
          {t('report.submit.submit')}
        </div>
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

  // Drag-and-drop reorder state (grip handle ⠿). `dragIndex` = row being dragged,
  // `overIndex` = row currently hovered as the drop target.
  const [dragIndex, setDragIndex] = useState<number | null>(null)
  const [overIndex, setOverIndex] = useState<number | null>(null)
  const resetDrag = (): void => {
    setDragIndex(null)
    setOverIndex(null)
  }
  const dropOn = (target: number): void => {
    if (dragIndex !== null && dragIndex !== target) move(dragIndex, target)
    resetDrag()
  }

  const onSubmit = (values: FormValues): void => {
    onSave({
      name: values.name,
      description: values.description.trim() === '' ? null : values.description,
      isDefault: values.isDefault,
      fields: values.fields.map((f, i) => ({
        name: deriveName(f.label, i, f.name),
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
      className="flex h-full flex-col"
    >
      {/* pinned toolbar (作業卓): template name + save (design handoff) */}
      <div className="relative z-10 flex flex-none items-center gap-3.5 border-b border-border bg-surface-raised px-6.5 py-3.5 shadow-toolbar">
        <div className="min-w-0">
          <label htmlFor="template-name" className="block text-caption text-fg-faint-2">
            {t('template.form.name')}
          </label>
          <input
            id="template-name"
            {...register('name')}
            className="w-85 max-w-full border-0 border-b-2 border-transparent bg-transparent py-0.5 text-lg font-bold text-fg outline-none focus:border-accent"
          />
        </div>
        <div className="flex-1" />
        <Button type="submit" disabled={isPending}>
          {t('common.actions.save')}
        </Button>
      </div>

      {/* two panes: editor (scrolls) + live preview (scrolls) */}
      <div className="flex min-h-0 flex-1 flex-col lg:flex-row">
        {/* editor pane */}
        <div className="flex min-w-0 flex-1 flex-col gap-2.75 overflow-y-auto border-border px-6.5 py-5.5 lg:border-r">
          {errorKey !== null && <InlineAlert variant="error">{t(errorKey)}</InlineAlert>}
          {errors.name !== undefined && (
            <InlineAlert variant="warn">{t('error.validation.required')}</InlineAlert>
          )}

          <p className="mb-0.5 text-label font-bold tracking-wide text-fg-faint-2">
            {t('template.form.fieldsLabel')} ・ {t('template.form.reorderHint')}
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
              isDragging={dragIndex === index}
              isOver={overIndex === index}
              onDragStart={() => {
                setDragIndex(index)
              }}
              onDragEnter={() => {
                setOverIndex(index)
              }}
              onDrop={() => {
                dropOn(index)
              }}
              onDragEnd={resetDrag}
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
            className="rounded-xl border-2 border-dashed border-border-strong py-3.5 text-center text-ui font-semibold text-accent-ink hover:bg-surface-overlay disabled:opacity-50"
          >
            ＋ {t('template.form.addField')}
          </button>
        </div>

        {/* preview pane (提出者の見え方) */}
        <div className="overflow-y-auto bg-surface-sunken px-5.5 py-5.5 lg:w-96 lg:flex-none">
          <p className="mb-3.25 text-label font-bold tracking-wide text-fg-faint-2">
            {t('template.form.preview')}
          </p>
          <PreviewPanel control={control} />
        </div>
      </div>
    </form>
  )
}
