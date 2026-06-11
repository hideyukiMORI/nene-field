<?php

declare(strict_types=1);

namespace NeneField\User;

interface GetUserUseCaseInterface
{
    public function execute(string $organizationId, string $userId): ?User;
}
