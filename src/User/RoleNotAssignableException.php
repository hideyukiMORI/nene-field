<?php

declare(strict_types=1);

namespace NeneField\User;

use NeneField\Auth\Role;
use RuntimeException;

/**
 * The requested role cannot be assigned through user management. `superadmin`
 * is provisioned out-of-band, never granted via the org-scoped users API, so a
 * compromised admin token cannot escalate to cross-tenant access.
 */
final class RoleNotAssignableException extends RuntimeException
{
    public function __construct(Role $role)
    {
        parent::__construct(sprintf('The role "%s" cannot be assigned through user management.', $role->value));
    }
}
