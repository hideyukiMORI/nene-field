/** Wire DTO (snake_case) for an organization — see docs/openapi/openapi.yaml. */

export interface OrganizationDto {
  organization_id: string
  name: string
  slug: string
  custom_domain?: string | null
  is_active: boolean
  ai_summary_enabled: boolean
  notification_email?: string | null
  webhook_url?: string | null
  created_at: string
  updated_at: string
}
