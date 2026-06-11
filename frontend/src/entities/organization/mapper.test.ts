import { describe, expect, it } from 'vitest'
import type { OrganizationDto } from './api-types'
import { toOrganization } from './mapper'

const dto: OrganizationDto = {
  organization_id: 'org-1',
  name: '山田造園',
  slug: 'yamada',
  custom_domain: null,
  is_active: true,
  ai_summary_enabled: false,
  notification_email: 'kanri@example.com',
  webhook_url: null,
  created_at: '2026-06-01 00:00:00',
  updated_at: '2026-06-10 00:00:00',
}

describe('organization mapper', () => {
  it('maps the DTO to the model', () => {
    const org = toOrganization(dto)
    expect(org.id).toBe('org-1')
    expect(org.name).toBe('山田造園')
    expect(org.aiSummaryEnabled).toBe(false)
    expect(org.notificationEmail).toBe('kanri@example.com')
    expect(org.webhookUrl).toBeNull()
  })

  it('defaults nullable fields', () => {
    const org = toOrganization({ ...dto, notification_email: null, custom_domain: null })
    expect(org.notificationEmail).toBeNull()
    expect(org.customDomain).toBeNull()
  })
})
