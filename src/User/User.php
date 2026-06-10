<?php

declare(strict_types=1);

namespace NeneField\User;

use NeneField\Auth\Role;

/**
 * Operator account. Belongs to exactly one organization (multi-tenancy.md).
 * `passwordHash` never leaves the domain — {@see UserResponse} omits it.
 */
final readonly class User
{
    public function __construct(
        public string $userId,
        public string $organizationId,
        public string $name,
        public string $email,
        public string $passwordHash,
        public Role $role,
        public bool $isActive,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
    ) {
    }
}
