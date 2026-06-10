<?php

declare(strict_types=1);

namespace NeneField\Auth;

use NeneField\User\User;
use NeneField\User\UserRepositoryInterface;

final readonly class GetCurrentUserUseCase implements GetCurrentUserUseCaseInterface
{
    public function __construct(
        private UserRepositoryInterface $users,
    ) {
    }

    public function execute(string $organizationId, string $userId): ?User
    {
        return $this->users->findById($organizationId, $userId);
    }
}
