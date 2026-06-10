<?php

declare(strict_types=1);

namespace NeneField\Auth;

final readonly class ChangePasswordInput
{
    public function __construct(
        public string $organizationId,
        public string $userId,
        public string $currentPassword,
        public string $newPassword,
    ) {
    }
}
