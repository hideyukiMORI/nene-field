<?php

declare(strict_types=1);

namespace NeneField\Report;

use NeneField\Auth\Role;

interface GetReportUseCaseInterface
{
    /**
     * Returns the report if the actor may see it (owner, or approver/admin in the
     * org), otherwise null (404 — existence is not disclosed).
     */
    public function execute(string $organizationId, string $reportId, string $actorId, Role $actorRole): ?Report;
}
