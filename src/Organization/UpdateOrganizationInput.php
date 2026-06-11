<?php

declare(strict_types=1);

namespace NeneField\Organization;

/**
 * Partial update of an organization's settings. Non-nullable columns use `null`
 * to mean "not provided"; the three nullable columns
 * (`notification_email` / `webhook_url` / `custom_domain`) carry an explicit
 * `*Provided` flag so a client can clear them to `null` without that being
 * confused with "absent".
 *
 * `slug`, `customDomain`, and `isActive` are superadmin-only; the use case
 * applies them only when {@see $isSuperadmin} is true and otherwise ignores them.
 */
final readonly class UpdateOrganizationInput
{
    public function __construct(
        public string $organizationId,
        public ?string $actorId,
        public bool $isSuperadmin,
        public ?string $name,
        public ?bool $aiSummaryEnabled,
        public bool $notificationEmailProvided,
        public ?string $notificationEmail,
        public bool $webhookUrlProvided,
        public ?string $webhookUrl,
        public ?string $slug,
        public bool $customDomainProvided,
        public ?string $customDomain,
        public ?bool $isActive,
    ) {
    }
}
