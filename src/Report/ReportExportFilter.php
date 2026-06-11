<?php

declare(strict_types=1);

namespace NeneField\Report;

/**
 * Criteria for a report CSV export. The work-date range is mandatory (bounded
 * export); everything else narrows it. No pagination — the export returns every
 * matching row up to a safety cap enforced by the repository.
 */
final readonly class ReportExportFilter
{
    /**
     * @param list<ReportStatus> $statuses
     */
    public function __construct(
        public string $workDateFrom,
        public string $workDateTo,
        public array $statuses,
        public ?string $userId = null,
        public ?string $projectCode = null,
    ) {
    }
}
