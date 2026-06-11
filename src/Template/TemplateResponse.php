<?php

declare(strict_types=1);

namespace NeneField\Template;

/**
 * Public JSON presenter for a {@see ReportTemplate} (OpenAPI `TemplateResponse`).
 * Also the sanitized snapshot source for audit before/after (audit-logging.md §5).
 */
final readonly class TemplateResponse
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(ReportTemplate $template): array
    {
        return [
            'template_id' => $template->templateId,
            'organization_id' => $template->organizationId,
            'name' => $template->name,
            'description' => $template->description,
            'fields' => array_map(static fn (TemplateField $f): array => $f->toArray(), $template->fields),
            'is_default' => $template->isDefault,
            'created_at' => $template->createdAt,
            'updated_at' => $template->updatedAt,
        ];
    }
}
