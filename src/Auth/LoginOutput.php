<?php

declare(strict_types=1);

namespace NeneField\Auth;

use NeneField\User\User;

final readonly class LoginOutput
{
    public function __construct(
        public string $token,
        public User $user,
    ) {
    }
}
