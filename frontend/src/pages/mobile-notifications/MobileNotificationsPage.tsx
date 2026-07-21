import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useTranslation } from '@/shared/i18n'
import { NotificationList } from '@/shared/ui'
import type { NotificationItem } from '@/shared/ui'

const SEED: NotificationItem[] = [
  {
    id: 'n1',
    type: 'rejected',
    title: '配筋検査が差し戻されました',
    sub: '写真を追加して再提出してください',
    time: '1時間前',
    unread: true,
  },
  {
    id: 'n2',
    type: 'approved',
    title: '現場A 基礎打設が承認されました',
    sub: '佐藤 承認者',
    time: '昨日',
    unread: false,
  },
]

/** Mobile notifications screen (design handoff §5.2). */
export function MobileNotificationsPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const [items, setItems] = useState<NotificationItem[]>(SEED)

  return (
    <div className="flex flex-col">
      <header className="flex items-center gap-3 border-b border-border bg-surface-raised px-4 py-3">
        <button
          type="button"
          aria-label={t('common.actions.back')}
          onClick={() => {
            void navigate(-1)
          }}
          className="text-lg text-text-muted"
        >
          ‹
        </button>
        <h1 className="text-base font-bold text-text-primary">{t('shell.notifications.title')}</h1>
      </header>
      <NotificationList
        items={items}
        markAllLabel={t('shell.notifications.markAll')}
        unreadLabel={t('shell.notifications.unread')}
        emptyLabel={t('shell.notifications.empty')}
        onMarkAllRead={() => {
          setItems((prev) => prev.map((n) => ({ ...n, unread: false })))
        }}
        onSelect={(id) => {
          setItems((prev) => prev.map((n) => (n.id === id ? { ...n, unread: false } : n)))
        }}
      />
    </div>
  )
}
