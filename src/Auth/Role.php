<?php

declare(strict_types=1);

namespace NeneField\Auth;

/**
 * Operator roles (docs/terms.md §3).
 *
 * - submitter: submits reports
 * - approver: approves / rejects submitted reports
 * - admin: organization-scoped management (users, templates, settings, export, audit)
 * - superadmin: cross-organization access (provisions organizations)
 */
enum Role: string
{
    case Submitter = 'submitter';
    case Approver = 'approver';
    case Admin = 'admin';
    case Superadmin = 'superadmin';

    /** Organization-level management (users, templates, settings, export, audit). */
    public function canManageOrganization(): bool
    {
        return $this === self::Admin || $this === self::Superadmin;
    }

    /** Approve / reject submitted reports. */
    public function canApprove(): bool
    {
        return $this === self::Approver || $this->canManageOrganization();
    }
}
