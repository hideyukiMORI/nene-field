<?php

declare(strict_types=1);

namespace NeneField\User;

final readonly class ListUsersUseCase implements ListUsersUseCaseInterface
{
    public function __construct(
        private UserRepositoryInterface $users,
    ) {
    }

    public function execute(string $organizationId, int $limit, int $offset): ListUsersOutput
    {
        return new ListUsersOutput(
            items: $this->users->listByOrg($organizationId, $limit, $offset),
            total: $this->users->countByOrg($organizationId),
            limit: $limit,
            offset: $offset,
        );
    }
}
