<?php

declare(strict_types=1);

namespace NeneField\Report;

use RuntimeException;

/**
 * The report does not exist in the caller's organization, or the caller is not
 * permitted to see it (non-owner submitter) — both surface as 404 so existence
 * is not disclosed across tenants/users.
 */
final class ReportNotFoundException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The report was not found.');
    }
}
