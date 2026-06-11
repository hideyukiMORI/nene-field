import { Link } from 'react-router-dom'
import { useTranslation } from '@/shared/i18n'
import { Badge, Button, EmptyState, ErrorState, LoadingState } from '@/shared/ui'
import { useTemplateList } from '../hooks/use-template-list'

export function TemplateList() {
  const { t } = useTranslation()
  const { templates, isLoading, isError, refetch, remove, isDeleting } = useTemplateList()

  if (isLoading) {
    return <LoadingState label={t('common.state.loading')} />
  }
  if (isError) {
    return (
      <ErrorState
        message={t('template.list.error')}
        retryLabel={t('common.actions.retry')}
        onRetry={refetch}
      />
    )
  }
  if (templates.length === 0) {
    return <EmptyState message={t('template.list.empty')} />
  }

  return (
    <ul className="divide-y divide-border border border-border bg-surface-raised">
      {templates.map((template) => (
        <li key={template.id} className="flex items-center justify-between gap-3 px-4 py-3">
          <div className="flex items-center gap-3">
            <span className="text-sm font-medium text-fg">{template.name}</span>
            {template.isDefault && <Badge tone="success">{t('template.list.default')}</Badge>}
            <span className="text-xs text-fg-muted">
              {t('template.list.fieldCount', { count: template.fields.length })}
            </span>
          </div>
          <div className="flex gap-2">
            <Link
              to={`/templates/${template.id}/edit`}
              className="inline-flex items-center border border-border-strong px-3 py-1 text-sm font-medium text-fg"
            >
              {t('common.actions.edit')}
            </Link>
            <Button
              variant="danger"
              disabled={isDeleting}
              onClick={() => {
                if (window.confirm(t('template.delete.confirm'))) {
                  remove(template.id)
                }
              }}
            >
              {t('common.actions.delete')}
            </Button>
          </div>
        </li>
      ))}
    </ul>
  )
}
