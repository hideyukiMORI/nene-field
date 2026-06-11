<?php

declare(strict_types=1);

namespace NeneField\User;

interface CreateUserUseCaseInterface
{
    /**
     * @throws RoleNotAssignableException when attempting to assign `superadmin`
     * @throws UserEmailConflictException when the email is already used in the org
     */
    public function execute(CreateUserInput $input): User;
}
