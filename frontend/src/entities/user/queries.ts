import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient } from '@/shared/api/client'
import type { AppError } from '@/shared/api/errors'
import type { UserDto, UserListResponseDto } from './api-types'
import { toUser, toUserList } from './mapper'
import type { User, UserList } from './model'
import { userKeys, type UserListParams } from './query-keys'

export function useUserListQuery(params: UserListParams): UseQueryResult<UserList, AppError> {
  return useQuery<UserList, AppError>({
    queryKey: userKeys.list(params),
    queryFn: async () => {
      const search = new URLSearchParams({
        limit: String(params.limit),
        offset: String(params.offset),
      })
      const dto = await apiClient.get<UserListResponseDto>(`/users?${search.toString()}`)
      return toUserList(dto)
    },
  })
}

export function useUserQuery(
  id: string,
  options?: { enabled?: boolean },
): UseQueryResult<User, AppError> {
  return useQuery<User, AppError>({
    queryKey: userKeys.detail(id),
    enabled: options?.enabled ?? true,
    queryFn: async () => {
      const dto = await apiClient.get<UserDto>(`/users/${encodeURIComponent(id)}`)
      return toUser(dto)
    },
  })
}
