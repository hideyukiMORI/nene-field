import type { OrganizationId } from './ids'

/** The tenant root (UI model). The AI secret is never modelled (never returned). */
export interface Organization {
  id: OrganizationId
  name: string
  slug: string
  customDomain: string | null
  isActive: boolean
  aiSummaryEnabled: boolean
  notificationEmail: string | null
  webhookUrl: string | null
  createdAt: string
  updatedAt: string
}
