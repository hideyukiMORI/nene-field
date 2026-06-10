<?php

declare(strict_types=1);

namespace NeneField\Auth;

use RuntimeException;

/**
 * Thrown when an email/password pair does not match an active user. The message
 * is deliberately generic so account existence/status is not disclosed.
 */
final class InvalidCredentialsException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The email or password is incorrect.');
    }
}
