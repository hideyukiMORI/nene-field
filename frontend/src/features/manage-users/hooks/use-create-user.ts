import { useCreateUserMutation, type CreateUserInput } from '@/entities/user'
import type { MessageKey } from '@/shared/i18n'

export interface CreateUserState {
  create: (input: CreateUserInput) => void
  isPending: boolean
  errorKey: MessageKey | null
}

export function useCreateUser(onDone: () => void): CreateUserState {
  const mutation = useCreateUserMutation()

  return {
    create: (input) => {
      mutation.mutate(input, {
        onSuccess: () => {
          onDone()
        },
      })
    },
    isPending: mutation.isPending,
    errorKey: mutation.error !== null ? 'user.form.error' : null,
  }
}
