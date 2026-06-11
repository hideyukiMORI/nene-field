<?php

declare(strict_types=1);

namespace NeneField\Organization;

final readonly class CreateOrganizationInput
{
    public function __construct(
        public ?string $actorId,
        public string $name,
        public string $slug,
        public ?string $customDomain,
        public bool $isActive,
    ) {
    }
}
