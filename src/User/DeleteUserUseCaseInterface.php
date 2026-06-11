<?php

declare(strict_types=1);

namespace NeneField\User;

interface DeleteUserUseCaseInterface
{
    /**
     * @throws UserNotFoundException     when the user is missing or in another org
     * @throws CannotDeleteSelfException when the actor targets their own account
     */
    public function execute(string $organizationId, ?string $actorId, string $userId): void;
}
