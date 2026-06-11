<?php

declare(strict_types=1);

namespace NeneField\Template;

/**
 * Partial update of a template. `null` (or `*Provided = false`) means "not
 * provided — keep the existing value".
 */
final readonly class UpdateTemplateInput
{
    /**
     * @param list<TemplateField>|null $fields
     */
    public function __construct(
        public string $organizationId,
        public ?string $actorId,
        public string $templateId,
        public ?string $name,
        public bool $descriptionProvided,
        public ?string $description,
        public ?array $fields,
        public ?bool $isDefault,
    ) {
    }
}
