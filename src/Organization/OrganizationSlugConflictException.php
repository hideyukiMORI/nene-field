<?php

declare(strict_types=1);

namespace NeneField\Organization;

use RuntimeException;

/**
 * The slug (tenant-resolution key, ADR 0013) or custom domain is already taken
 * by another organization. Surfaces as 409 Conflict.
 */
final class OrganizationSlugConflictException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('An organization with this slug or custom domain already exists.');
    }
}
