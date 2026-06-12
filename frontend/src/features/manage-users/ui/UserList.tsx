import { useState } from 'react'
import { getCurrentUser } from '@/entities/auth'
import {
  ASSIGNABLE_USER_ROLES,
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
  return ASSIGNABLE_USER_ROLES[(i + 1) % ASSIGNABLE_USER_ROLES.length]
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
  const currentUserId = getCurrentUser()?.id ?? null

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
    <div className="flex flex-col gap-4">
      <div className="flex flex-wrap items-center gap-3">
        <h2 className="text-xl font-bold text-fg">{t('user.list.title')}</h2>
        <span className="text-sm text-fg-muted tabular-nums">
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

      {users.length === 0 ? (
        <EmptyState message={t('user.list.empty')} />
      ) : (
        <div className="overflow-hidden rounded-card border border-border bg-surface-raised">
          <TableWrap>
            <Table className="min-w-160">
              <thead>
                <Tr>
                  <Th>{t('user.list.colName')}</Th>
                  <Th className="w-32">{t('user.list.colRole')}</Th>
                  <Th className="w-24">{t('user.list.colStatus')}</Th>
                  <Th className="w-44" />
                </Tr>
              </thead>
              <tbody>
                {users.map((user) => {
                  const isSelf = user.id === currentUserId
                  return (
                    <Tr key={user.id} className={user.isActive ? '' : 'opacity-60'}>
                      <Td>
                        <div className="flex items-center gap-3">
                          <span className="grid h-9 w-9 flex-none place-items-center rounded-pill bg-accent-soft text-sm font-bold text-accent-ink">
                            {user.name.slice(0, 1)}
                          </span>
                          <div className="min-w-0">
                            <span className="flex items-center gap-2 font-medium text-fg">
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
                          disabled={!isAssignable(user.role) || updateMutation.isPending}
                          className="disabled:cursor-not-allowed"
                        >
                          <Badge tone="info">{t(roleLabelKey[user.role])}</Badge>
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
        </div>
      )}

      <Modal
        open={inviteOpen}
        onClose={() => {
          setInviteOpen(false)
        }}
        title={t('user.invite.title')}
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
