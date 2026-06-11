<?php

declare(strict_types=1);

namespace NeneField\User;

interface UpdateUserUseCaseInterface
{
    /**
     * @throws UserNotFoundException      when the user is missing or in another org
     * @throws RoleNotAssignableException when attempting to assign `superadmin`
     */
    public function execute(UpdateUserInput $input): User;
}
