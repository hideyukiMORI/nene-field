<?php

declare(strict_types=1);

namespace NeneField\User;

interface ListUsersUseCaseInterface
{
    public function execute(string $organizationId, int $limit, int $offset): ListUsersOutput;
}
