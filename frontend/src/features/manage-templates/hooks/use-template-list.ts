import {
  useDeleteTemplateMutation,
  useTemplateListQuery,
  type Template,
} from '@/entities/report-template'

export interface TemplateListState {
  templates: Template[]
  isLoading: boolean
  isError: boolean
  refetch: () => void
  remove: (id: string) => void
  isDeleting: boolean
}

export function useTemplateList(): TemplateListState {
  const query = useTemplateListQuery()
  const deleteMutation = useDeleteTemplateMutation()

  return {
    templates: query.data ?? [],
    isLoading: query.isLoading,
    isError: query.isError,
    refetch: () => {
      void query.refetch()
    },
    remove: (id) => {
      deleteMutation.mutate(id)
    },
    isDeleting: deleteMutation.isPending,
  }
}
