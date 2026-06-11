<?php

declare(strict_types=1);

namespace NeneField\User;

use NeneField\Auth\Role;

/**
 * Partial update of a user. A `null` field means "not provided — keep the
 * existing value"; `email` is immutable and therefore not present here.
 */
final readonly class UpdateUserInput
{
    public function __construct(
        public string $organizationId,
        public ?string $actorId,
        public string $userId,
        public ?string $name,
        public ?Role $role,
        public ?bool $isActive,
    ) {
    }
}
