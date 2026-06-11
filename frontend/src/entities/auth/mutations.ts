import { useMutation, type UseMutationResult } from '@tanstack/react-query'
import { apiClient, setAuthToken } from '@/shared/api/client'
import type { AppError } from '@/shared/api/errors'
import type { LoginResponseDto } from './api-types'
import { toSignInResult } from './mapper'
import type { SignInResult } from './model'
import { setCurrentUser } from './session'

export interface SignInInput {
  email: string
  password: string
}

/** Logs in, then sets the in-memory token + current user (reveals the app shell). */
export function useSignInMutation(): UseMutationResult<SignInResult, AppError, SignInInput> {
  return useMutation<SignInResult, AppError, SignInInput>({
    mutationFn: async (input) => {
      const dto = await apiClient.post<LoginResponseDto>('/auth/login', { ...input })
      return toSignInResult(dto)
    },
    onSuccess: (result) => {
      setAuthToken(result.token)
      setCurrentUser(result.user)
    },
  })
}
