<?php

declare(strict_types=1);

namespace NeneField\Auth;

interface LoginUseCaseInterface
{
    public function execute(LoginInput $input): LoginOutput;
}
