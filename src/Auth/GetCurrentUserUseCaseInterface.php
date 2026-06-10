<?php

declare(strict_types=1);

namespace NeneField\Auth;

use NeneField\User\User;

interface GetCurrentUserUseCaseInterface
{
    public function execute(string $organizationId, string $userId): ?User;
}
