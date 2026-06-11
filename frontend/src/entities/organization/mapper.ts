import type { OrganizationDto } from './api-types'
import { toOrganizationId } from './ids'
import type { Organization } from './model'

export function toOrganization(dto: OrganizationDto): Organization {
  return {
    id: toOrganizationId(dto.organization_id),
    name: dto.name,
    slug: dto.slug,
    customDomain: dto.custom_domain ?? null,
    isActive: dto.is_active,
    aiSummaryEnabled: dto.ai_summary_enabled,
    notificationEmail: dto.notification_email ?? null,
    webhookUrl: dto.webhook_url ?? null,
    createdAt: dto.created_at,
    updatedAt: dto.updated_at,
  }
}
