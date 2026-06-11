<?php

declare(strict_types=1);

namespace NeneField\User;

final readonly class GetUserUseCase implements GetUserUseCaseInterface
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
