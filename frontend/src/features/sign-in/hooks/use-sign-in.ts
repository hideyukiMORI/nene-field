import { useSignInMutation, type SignInInput } from '@/entities/auth'
import type { MessageKey } from '@/shared/i18n'

/**
 * Sign-in workflow. Exposes the submit action, pending state, and a translated
 * error key derived from the API error slug (the API is authoritative; the form
 * only reflects its outcome).
 */
export function useSignIn(): {
  signIn: (input: SignInInput) => void
  isPending: boolean
  errorKey: MessageKey | null
} {
  const mutation = useSignInMutation()

  let errorKey: MessageKey | null = null
  if (mutation.error !== null) {
    errorKey = mutation.error.status === 401 ? 'auth.login.invalid' : 'error.generic'
  }

  return {
    signIn: (input) => {
      mutation.mutate(input)
    },
    isPending: mutation.isPending,
    errorKey,
  }
}
