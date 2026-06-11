import { useMutation, useQueryClient, type UseMutationResult } from '@tanstack/react-query'
import { apiClient } from '@/shared/api/client'
import type { AppError } from '@/shared/api/errors'
import type { UserDto } from './api-types'
import type { AssignableUserRole } from './enum'
import { toUser } from './mapper'
import type { User } from './model'
import { userKeys } from './query-keys'

export interface CreateUserInput {
  name: string
  email: string
  role: AssignableUserRole
  password: string
}

export interface UpdateUserInput {
  id: string
  name: string
  role: AssignableUserRole
  isActive: boolean
}

export function useCreateUserMutation(): UseMutationResult<User, AppError, CreateUserInput> {
  const queryClient = useQueryClient()
  return useMutation<User, AppError, CreateUserInput>({
    mutationFn: async (input) => {
      const dto = await apiClient.post<UserDto>('/users', {
        name: input.name,
        email: input.email,
        role: input.role,
        password: input.password,
      })
      return toUser(dto)
    },
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: userKeys.all })
    },
  })
}

export function useUpdateUserMutation(): UseMutationResult<User, AppError, UpdateUserInput> {
  const queryClient = useQueryClient()
  return useMutation<User, AppError, UpdateUserInput>({
    mutationFn: async (input) => {
      const dto = await apiClient.put<UserDto>(`/users/${encodeURIComponent(input.id)}`, {
        name: input.name,
        role: input.role,
        is_active: input.isActive,
      })
      return toUser(dto)
    },
    onSuccess: (user) => {
      void queryClient.invalidateQueries({ queryKey: userKeys.all })
      void queryClient.invalidateQueries({ queryKey: userKeys.detail(user.id) })
    },
  })
}

export function useDeleteUserMutation(): UseMutationResult<undefined, AppError, string> {
  const queryClient = useQueryClient()
  return useMutation<undefined, AppError, string>({
    mutationFn: async (id) => {
      await apiClient.delete(`/users/${encodeURIComponent(id)}`)
      return undefined
    },
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: userKeys.all })
    },
  })
}
