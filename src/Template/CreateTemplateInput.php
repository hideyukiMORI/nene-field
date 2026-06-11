<?php

declare(strict_types=1);

namespace NeneField\Template;

final readonly class CreateTemplateInput
{
    /**
     * @param list<TemplateField> $fields
     */
    public function __construct(
        public string $organizationId,
        public ?string $actorId,
        public string $name,
        public ?string $description,
        public array $fields,
        public bool $isDefault,
    ) {
    }
}
