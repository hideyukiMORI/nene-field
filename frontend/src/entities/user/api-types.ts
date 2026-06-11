/** Wire DTOs (snake_case) for users — see docs/openapi/openapi.yaml. */

export interface UserDto {
  user_id: string
  organization_id: string
  name: string
  email: string
  role: string
  is_active: boolean
  created_at: string
  updated_at: string
}

export interface UserListResponseDto {
  items: UserDto[]
  limit: number
  offset: number
  total?: number
}
