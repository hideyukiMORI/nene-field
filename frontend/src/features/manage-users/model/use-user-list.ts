import { useDeleteUserMutation, useUserListQuery, type User } from '@/entities/user'

export interface UserListState {
  users: User[]
  isLoading: boolean
  isError: boolean
  refetch: () => void
  remove: (id: string) => void
  isDeleting: boolean
}

export function useUserList(): UserListState {
  const query = useUserListQuery({ limit: 100, offset: 0 })
  const deleteMutation = useDeleteUserMutation()

  return {
    users: query.data?.items ?? [],
    isLoading: query.isLoading,
    isError: query.isError,
    refetch: () => {
      void query.refetch()
    },
    remove: (id) => {
      deleteMutation.mutate(id)
    },
    isDeleting: deleteMutation.isPending,
  }
}
