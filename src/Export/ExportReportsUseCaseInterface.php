<?php

declare(strict_types=1);

namespace NeneField\Export;

use NeneField\Report\ReportExportFilter;

interface ExportReportsUseCaseInterface
{
    public function execute(string $organizationId, ?string $actorId, ReportExportFilter $filter): ReportExport;
}
