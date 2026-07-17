import { useUpdateUserMutation, useUserQuery, type User } from '@/entities/user'
import type { AssignableUserRole } from '@/entities/user'
import type { MessageKey } from '@/shared/i18n'

export interface EditUserPayload {
  name: string
  role: AssignableUserRole
  isActive: boolean
}

export interface EditUserState {
  initial: User | undefined
  isLoading: boolean
  save: (payload: EditUserPayload) => void
  isPending: boolean
  errorKey: MessageKey | null
}

export function useEditUser(userId: string, onDone: () => void): EditUserState {
  const detail = useUserQuery(userId)
  const mutation = useUpdateUserMutation()

  return {
    initial: detail.data,
    isLoading: detail.isLoading,
    save: (payload) => {
      mutation.mutate(
        { id: userId, ...payload },
        {
          onSuccess: () => {
            onDone()
          },
        },
      )
    },
    isPending: mutation.isPending,
    errorKey: mutation.error !== null ? 'user.form.error' : null,
  }
}
