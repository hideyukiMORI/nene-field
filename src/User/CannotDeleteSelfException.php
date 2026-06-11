<?php

declare(strict_types=1);

namespace NeneField\User;

use RuntimeException;

/**
 * An admin cannot delete their own account, which would orphan the session and
 * could remove the last administrator. Surfaces as 409 Conflict.
 */
final class CannotDeleteSelfException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('You cannot delete your own account.');
    }
}
