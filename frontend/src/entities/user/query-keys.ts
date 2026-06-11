export interface UserListParams {
  limit: number
  offset: number
}

export const userKeys = {
  all: ['users'] as const,
  list: (params: UserListParams) => ['users', 'list', params] as const,
  detail: (id: string) => ['users', 'detail', id] as const,
}
