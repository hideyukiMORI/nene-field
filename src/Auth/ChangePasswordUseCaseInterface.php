<?php

declare(strict_types=1);

namespace NeneField\Auth;

interface ChangePasswordUseCaseInterface
{
    /** @throws InvalidCredentialsException when the current password is wrong. */
    public function execute(ChangePasswordInput $input): void;
}
