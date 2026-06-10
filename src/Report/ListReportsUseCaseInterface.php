<?php

declare(strict_types=1);

namespace NeneField\Report;

use NeneField\Auth\Role;

interface ListReportsUseCaseInterface
{
    public function execute(string $organizationId, string $actorId, Role $actorRole, ReportFilter $filter): ListReportsOutput;
}
