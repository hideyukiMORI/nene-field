<?php

declare(strict_types=1);

namespace NeneField\Organization;

use RuntimeException;

/**
 * The organization does not exist. Surfaces as 404 Not Found.
 */
final class OrganizationNotFoundException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The organization was not found.');
    }
}
