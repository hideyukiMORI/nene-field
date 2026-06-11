<?php

declare(strict_types=1);

namespace NeneField\User;

use NeneField\Auth\Role;

final readonly class CreateUserInput
{
    public function __construct(
        public string $organizationId,
        public ?string $actorId,
        public string $name,
        public string $email,
        public Role $role,
        public string $password,
    ) {
    }
}
