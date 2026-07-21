import { Link } from 'react-router-dom'
import { useTranslation } from '@/shared/i18n'
import {
  Badge,
  Button,
  EmptyState,
  ErrorState,
  LoadingState,
  Table,
  TableWrap,
  Td,
  Th,
  Tr,
} from '@/shared/ui'
import { useTemplateList } from '../model/use-template-list'

/**
 * Template list (admin). Pinned-toolbar (作業卓) screen consistent with the user
 * and report lists: a flex-none white toolbar over a single scrolling table.
 */
export function TemplateList() {
  const { t } = useTranslation()
  const { templates, isLoading, isError, refetch, remove, isDeleting } = useTemplateList()

  const toolbar = (
    <div className="relative z-10 flex flex-none flex-wrap items-center gap-3 border-b border-border bg-surface-raised px-6.5 py-4 shadow-x-toolbar">
      <h2 className="text-lg font-bold text-text-primary">{t('template.list.title')}</h2>
      <span className="text-label text-text-faint tabular-nums">
        {t('template.list.count', { count: templates.length })}
      </span>
      <Link to="/templates/new" className="ml-auto">
        <Button>＋ {t('template.list.newAction')}</Button>
      </Link>
    </div>
  )

  return (
    <div className="flex h-full flex-col">
      {toolbar}
      <div className="min-h-0 flex-1 overflow-y-auto px-6.5 pt-2 pb-6">
        {isLoading ? (
          <LoadingState label={t('common.state.loading')} />
        ) : isError ? (
          <ErrorState
            message={t('template.list.error')}
            retryLabel={t('common.actions.retry')}
            onRetry={refetch}
          />
        ) : templates.length === 0 ? (
          <EmptyState message={t('template.list.empty')} />
        ) : (
          <TableWrap>
            <Table className="min-w-160">
              <thead>
                <Tr>
                  <Th>{t('template.list.colName')}</Th>
                  <Th className="w-28">{t('template.list.colFields')}</Th>
                  <Th className="w-48 text-right">{t('template.list.colActions')}</Th>
                </Tr>
              </thead>
              <tbody>
                {templates.map((template) => (
                  <Tr key={template.id}>
                    <Td>
                      <div className="flex items-center gap-2.5">
                        <span className="grid h-9 w-9 flex-none place-items-center rounded-x-pill bg-accent-soft text-base text-on-accent">
                          ◳
                        </span>
                        <span className="font-semibold text-text-primary">{template.name}</span>
                        {template.isDefault && (
                          <Badge tone="approved">{t('template.list.default')}</Badge>
                        )}
                      </div>
                    </Td>
                    <Td className="text-text-muted tabular-nums">
                      {t('template.list.fieldCount', { count: template.fields.length })}
                    </Td>
                    <Td>
                      <div className="flex justify-end gap-2">
                        <Link to={`/templates/${template.id}/edit`}>
                          <Button variant="ghost" size="sm">
                            {t('common.actions.edit')}
                          </Button>
                        </Link>
                        <Button
                          variant="danger-ghost"
                          size="sm"
                          disabled={isDeleting}
                          onClick={() => {
                            if (window.confirm(t('template.delete.confirm'))) remove(template.id)
                          }}
                        >
                          {t('common.actions.delete')}
                        </Button>
                      </div>
                    </Td>
                  </Tr>
                ))}
              </tbody>
            </Table>
          </TableWrap>
        )}
      </div>
    </div>
  )
}
