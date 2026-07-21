import { cn } from '@/shared/lib/cn'

export type NotificationType = 'submitted' | 'approved' | 'rejected' | 'system'

export interface NotificationItem {
  id: string
  type: NotificationType
  title: string
  sub: string
  time: string
  unread: boolean
}

const typeStyle: Record<NotificationType, { wrap: string; glyph: string }> = {
  submitted: { wrap: 'bg-x-submitted-soft text-x-submitted', glyph: '⬆' },
  approved: { wrap: 'bg-x-approved-soft text-x-approved', glyph: '✓' },
  rejected: { wrap: 'bg-x-rejected-soft text-x-rejected', glyph: '✕' },
  system: { wrap: 'bg-warn-soft text-warn', glyph: '⚙' },
}

interface NotificationListProps {
  items: NotificationItem[]
  markAllLabel: string
  emptyLabel: string
  /** aria-label for the unread dot. I18N-18: pass t('shell.notifications.unread'). */
  unreadLabel: string
  onSelect: (id: string) => void
  onMarkAllRead: () => void
}

/**
 * Presentational notification list (design handoff §3.12). Shared by the PC
 * top-bar dropdown and the mobile notifications screen: type icon, title/sub,
 * time, unread dot, and a tinted background for unread rows.
 */
export function NotificationList({
  items,
  markAllLabel,
  emptyLabel,
  unreadLabel,
  onSelect,
  onMarkAllRead,
}: NotificationListProps) {
  return (
    <div className="flex flex-col">
      <div className="flex items-center justify-between border-b border-border px-4 py-2.5">
        <span className="text-sm font-bold text-text-primary">通知</span>
        <button
          type="button"
          onClick={onMarkAllRead}
          className="text-xs font-semibold text-accent hover:text-accent-hover"
        >
          {markAllLabel}
        </button>
      </div>
      {items.length === 0 ? (
        <p className="px-4 py-8 text-center text-sm text-text-faint">{emptyLabel}</p>
      ) : (
        <ul>
          {items.map((n) => {
            const s = typeStyle[n.type]
            return (
              <li key={n.id}>
                <button
                  type="button"
                  onClick={() => {
                    onSelect(n.id)
                  }}
                  className={cn(
                    'flex w-full items-start gap-3 border-b border-border px-4 py-3 text-left hover:bg-surface-overlay',
                    n.unread && 'bg-x-row-hover',
                  )}
                >
                  <span
                    className={cn(
                      'grid h-8 w-8 flex-none place-items-center rounded-full text-sm',
                      s.wrap,
                    )}
                  >
                    {s.glyph}
                  </span>
                  <span className="min-w-0 flex-1">
                    <span className="block truncate text-sm font-semibold text-text-primary">
                      {n.title}
                    </span>
                    <span className="block truncate text-xs text-text-muted">{n.sub}</span>
                    <span className="mt-0.5 block text-xs text-text-faint">{n.time}</span>
                  </span>
                  {n.unread && (
                    <span
                      className="mt-1 h-2 w-2 flex-none rounded-full bg-accent"
                      aria-label={unreadLabel}
                    />
                  )}
                </button>
              </li>
            )
          })}
        </ul>
      )}
    </div>
  )
}
