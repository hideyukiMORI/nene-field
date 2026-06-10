<?php

declare(strict_types=1);

namespace NeneField\Organization;

/**
 * The tenant root entity. Resolved per request by slug or custom domain
 * (multi-tenancy.md / ADR 0013). `aiApiKey` is intentionally NOT modelled here —
 * it is a secret that never leaves the settings use case (legal-compliance.md §5).
 */
final readonly class Organization
{
    public function __construct(
        public string $organizationId,
        public string $name,
        public string $slug,
        public bool $isActive,
        public ?string $customDomain = null,
        public bool $aiSummaryEnabled = false,
        public ?string $notificationEmail = null,
        public ?string $webhookUrl = null,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
    ) {
    }
}
