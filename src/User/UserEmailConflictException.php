<?php

declare(strict_types=1);

namespace NeneField\User;

use RuntimeException;

/**
 * The email is already used by another user in the same organization
 * (uniqueness is per tenant — multi-tenancy.md §6). Surfaces as 409 Conflict.
 */
final class UserEmailConflictException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('A user with this email already exists in the organization.');
    }
}
