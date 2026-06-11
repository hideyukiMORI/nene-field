<?php

declare(strict_types=1);

namespace NeneField\Organization;

/**
 * Public JSON presenter for an {@see Organization} (OpenAPI `OrganizationResponse`).
 * The single place that decides which org fields are exposed; the AI secret
 * (`ai_api_key`) and its endpoint are never modelled or returned
 * (legal-compliance.md §5). Also the sanitized snapshot source for audit
 * before/after (audit-logging.md §5).
 */
final readonly class OrganizationResponse
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(Organization $organization): array
    {
        return [
            'organization_id' => $organization->organizationId,
            'name' => $organization->name,
            'slug' => $organization->slug,
            'custom_domain' => $organization->customDomain,
            'is_active' => $organization->isActive,
            'ai_summary_enabled' => $organization->aiSummaryEnabled,
            'notification_email' => $organization->notificationEmail,
            'webhook_url' => $organization->webhookUrl,
            'created_at' => $organization->createdAt,
            'updated_at' => $organization->updatedAt,
        ];
    }
}
