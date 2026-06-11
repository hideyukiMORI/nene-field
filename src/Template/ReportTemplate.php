<?php

declare(strict_types=1);

namespace NeneField\Template;

/**
 * Reusable, org-scoped form definition (terms.md §1: `ReportTemplate`). Belongs
 * to exactly one organization; at most one template per org is the default.
 */
final readonly class ReportTemplate
{
    /**
     * @param list<TemplateField> $fields
     */
    public function __construct(
        public string $templateId,
        public string $organizationId,
        public string $name,
        public ?string $description,
        public array $fields,
        public bool $isDefault,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
    ) {
    }
}
