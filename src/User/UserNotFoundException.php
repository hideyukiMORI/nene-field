<?php

declare(strict_types=1);

namespace NeneField\User;

use RuntimeException;

/**
 * The user does not exist in the caller's organization. A user in another tenant
 * surfaces as not found so existence is not disclosed across organizations.
 */
final class UserNotFoundException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The user was not found.');
    }
}
