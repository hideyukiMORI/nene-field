import { useState } from 'react'
import { getCurrentUser } from '@/entities/auth'
import {
  ASSIGNABLE_USER_ROLES,
  toUserId,
  useCreateUserMutation,
  useUpdateUserMutation,
  type AssignableUserRole,
  type User,
  type UserRole,
} from '@/entities/user'
import { useTranslation } from '@/shared/i18n'
import type { MessageKey } from '@/shared/i18n'
import {
  Badge,
  Button,
  EmptyState,
  ErrorState,
  Field,
  Input,
  LoadingState,
  Modal,
  Select,
  Table,
  TableWrap,
  Td,
  Th,
  Tr,
  useToast,
} from '@/shared/ui'
import { useUserList } from '../hooks/use-user-list'

const roleLabelKey: Record<UserRole, MessageKey> = {
  submitter: 'user.role.submitter',
  approver: 'user.role.approver',
  admin: 'user.role.admin',
  superadmin: 'user.role.superadmin',
}

function nextRole(role: AssignableUserRole): AssignableUserRole {
  const i = ASSIGNABLE_USER_ROLES.indexOf(role)
  // (i + 1) % length is always a valid index into the non-empty tuple; the
  // fallback only exists to satisfy noUncheckedIndexedAccess and is never hit.
  return ASSIGNABLE_USER_ROLES[(i + 1) % ASSIGNABLE_USER_ROLES.length] ?? role
}

function isAssignable(role: UserRole): role is AssignableUserRole {
  return (ASSIGNABLE_USER_ROLES as readonly string[]).includes(role)
}

function tempPassword(): string {
  return `Tmp-${Math.random().toString(36).slice(2, 10)}A1`
}

export function UserList() {
  const { t } = useTranslation()
  const toast = useToast()
  const { users, isLoading, isError, refetch, remove, isDeleting } = useUserList()
  const updateMutation = useUpdateUserMutation()
  const createMutation = useCreateUserMutation()
  // entities/auth and entities/user each brand their own UserId (§7: no bare
  // string ids across slice boundaries), so the session's id — despite being
  // the same backend user_id value — isn't nominally comparable to a
  // entities/user User.id without an explicit re-brand via the canonical
  // entities/user constructor.
  const authUser = getCurrentUser()
  const currentUserId = authUser ? toUserId(authUser.id) : null

  const [inviteOpen, setInviteOpen] = useState(false)
  const [form, setForm] = useState({ name: '', email: '', role: 'submitter' as AssignableUserRole })

  const cycleRole = (user: User): void => {
    if (!isAssignable(user.role)) return
    updateMutation.mutate({
      id: user.id,
      name: user.name,
      role: nextRole(user.role),
      isActive: user.isActive,
    })
  }

  const toggleActive = (user: User): void => {
    if (!isAssignable(user.role)) return
    updateMutation.mutate({
      id: user.id,
      name: user.name,
      role: user.role,
      isActive: !user.isActive,
    })
  }

  const submitInvite = (): void => {
    if (form.name.trim() === '' || form.email.trim() === '') return
    createMutation.mutate(
      {
        name: form.name.trim(),
        email: form.email.trim(),
        role: form.role,
        password: tempPassword(),
      },
      {
        onSuccess: () => {
          setInviteOpen(false)
          setForm({ name: '', email: '', role: 'submitter' })
          toast.show(t('user.invite.submit'))
        },
      },
    )
  }

  if (isLoading) return <LoadingState label={t('common.state.loading')} />
  if (isError) {
    return (
      <ErrorState
        message={t('user.list.error')}
        retryLabel={t('common.actions.retry')}
        onRetry={refetch}
      />
    )
  }

  const activeCount = users.filter((u) => u.isActive).length

  return (
    <div className="flex h-full flex-col">
      {/* pinned toolbar (作業卓): flex-none white bar, table below scrolls */}
      <div className="relative z-10 flex flex-none flex-wrap items-center gap-3 border-b border-border bg-surface-raised px-6.5 py-4 shadow-toolbar">
        <h2 className="text-lg font-bold text-fg">{t('user.list.title')}</h2>
        <span className="text-sm text-fg-faint tabular-nums">
          {t('user.list.count', { active: activeCount, total: users.length })}
        </span>
        <Button
          className="ml-auto"
          onClick={() => {
            setInviteOpen(true)
          }}
        >
          ＋ {t('user.invite.title')}
        </Button>
      </div>

      <div className="min-h-0 flex-1 overflow-y-auto px-6.5 pt-2 pb-6">
        {users.length === 0 ? (
          <EmptyState message={t('user.list.empty')} />
        ) : (
          <TableWrap>
            <Table className="min-w-160">
              <thead>
                <Tr>
                  <Th>{t('user.list.colName')}</Th>
                  <Th className="w-32">{t('user.list.colRole')}</Th>
                  <Th className="w-24">{t('user.list.colStatus')}</Th>
                  <Th className="w-48 text-right">{t('user.list.colActions')}</Th>
                </Tr>
              </thead>
              <tbody>
                {users.map((user) => {
                  const isSelf = user.id === currentUserId
                  const canCycle = isAssignable(user.role) && !updateMutation.isPending
                  return (
                    <Tr key={user.id} className={user.isActive ? '' : 'opacity-60'}>
                      <Td>
                        <div className="flex items-center gap-3">
                          <span className="grid h-9 w-9 flex-none place-items-center rounded-pill bg-accent-soft text-sm font-bold text-accent-ink">
                            {user.name.slice(0, 1)}
                          </span>
                          <div className="min-w-0">
                            <span className="flex items-center gap-2 font-semibold text-fg">
                              {user.name}
                              {isSelf && <Badge tone="neutral">{t('user.list.selfBadge')}</Badge>}
                            </span>
                            <span className="block truncate text-xs text-fg-faint">
                              {user.email}
                            </span>
                          </div>
                        </div>
                      </Td>
                      <Td>
                        <button
                          type="button"
                          onClick={() => {
                            cycleRole(user)
                          }}
                          disabled={!canCycle}
                          title={t('user.list.colRole')}
                          className="disabled:cursor-not-allowed"
                        >
                          <Badge tone="info">
                            {t(roleLabelKey[user.role])}
                            {canCycle && <span className="opacity-60"> ⇄</span>}
                          </Badge>
                        </button>
                      </Td>
                      <Td>
                        <Badge tone={user.isActive ? 'approved' : 'neutral'}>
                          {t(user.isActive ? 'user.status.active' : 'user.status.inactive')}
                        </Badge>
                      </Td>
                      <Td>
                        <div className="flex justify-end gap-2">
                          {isAssignable(user.role) && (
                            <Button
                              variant="ghost"
                              size="sm"
                              disabled={updateMutation.isPending}
                              onClick={() => {
                                toggleActive(user)
                              }}
                            >
                              {t(user.isActive ? 'user.actions.disable' : 'user.actions.enable')}
                            </Button>
                          )}
                          {!isSelf && (
                            <Button
                              variant="danger-ghost"
                              size="sm"
                              disabled={isDeleting}
                              onClick={() => {
                                if (window.confirm(t('user.delete.confirm'))) remove(user.id)
                              }}
                            >
                              {t('common.actions.delete')}
                            </Button>
                          )}
                        </div>
                      </Td>
                    </Tr>
                  )
                })}
              </tbody>
            </Table>
          </TableWrap>
        )}
      </div>

      <Modal
        open={inviteOpen}
        onClose={() => {
          setInviteOpen(false)
        }}
        title={t('user.invite.title')}
        closeLabel={t('common.actions.close')}
        footer={
          <>
            <Button
              variant="ghost"
              onClick={() => {
                setInviteOpen(false)
              }}
            >
              {t('common.actions.cancel')}
            </Button>
            <Button
              onClick={submitInvite}
              disabled={
                createMutation.isPending || form.name.trim() === '' || form.email.trim() === ''
              }
            >
              {t('user.invite.submit')}
            </Button>
          </>
        }
      >
        <div className="flex flex-col gap-4">
          <Field label={t('user.form.name')} htmlFor="invite-name">
            <Input
              id="invite-name"
              value={form.name}
              onChange={(e) => {
                setForm((f) => ({ ...f, name: e.target.value }))
              }}
            />
          </Field>
          <Field label={t('user.form.email')} htmlFor="invite-email">
            <Input
              id="invite-email"
              type="email"
              value={form.email}
              onChange={(e) => {
                setForm((f) => ({ ...f, email: e.target.value }))
              }}
            />
          </Field>
          <Field label={t('user.form.role')} htmlFor="invite-role">
            <Select
              id="invite-role"
              value={form.role}
              onChange={(e) => {
                setForm((f) => ({ ...f, role: e.target.value as AssignableUserRole }))
              }}
            >
              {ASSIGNABLE_USER_ROLES.map((r) => (
                <option key={r} value={r}>
                  {t(roleLabelKey[r])}
                </option>
              ))}
            </Select>
          </Field>
          <p className="text-xs text-fg-faint">{t('user.invite.tempNote')}</p>
        </div>
      </Modal>
    </div>
  )
}
