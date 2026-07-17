import {
  useCreateTemplateMutation,
  useTemplateQuery,
  useUpdateTemplateMutation,
  type CreateTemplateInput,
  type Template,
} from '@/entities/report-template'
import type { MessageKey } from '@/shared/i18n'

export interface TemplateEditor {
  initial: Template | undefined
  isLoading: boolean
  save: (input: CreateTemplateInput) => void
  isPending: boolean
  errorKey: MessageKey | null
}

/**
 * Create/edit orchestration. With no `templateId` it creates; with one it loads
 * the template and updates it. `onDone` fires after a successful save.
 */
export function useTemplateEditor(
  templateId: string | undefined,
  onDone: () => void,
): TemplateEditor {
  const isEditing = templateId !== undefined
  const detail = useTemplateQuery(templateId ?? '', { enabled: isEditing })
  const createMutation = useCreateTemplateMutation()
  const updateMutation = useUpdateTemplateMutation()

  const errored = createMutation.error !== null || updateMutation.error !== null

  return {
    initial: isEditing ? detail.data : undefined,
    isLoading: isEditing && detail.isLoading,
    save: (input) => {
      if (isEditing) {
        updateMutation.mutate(
          { id: templateId, ...input },
          {
            onSuccess: () => {
              onDone()
            },
          },
        )
      } else {
        createMutation.mutate(input, {
          onSuccess: () => {
            onDone()
          },
        })
      }
    },
    isPending: createMutation.isPending || updateMutation.isPending,
    errorKey: errored ? 'template.form.error' : null,
  }
}
