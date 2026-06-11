<?php

declare(strict_types=1);

namespace NeneField\Report;

/**
 * One denormalized row of a report CSV export (includes the resolved
 * `userName` from the users join). Distinct from {@see ReportSummary}: it carries
 * the approval columns needed for payroll/billing data, and no pagination.
 */
final readonly class ReportExportRow
{
    /**
     * @param list<string> $tags
     */
    public function __construct(
        public string $reportId,
        public string $workDate,
        public string $userId,
        public string $userName,
        public string $title,
        public ReportStatus $status,
        public ?string $projectCode,
        public array $tags,
        public ?string $submittedAt,
        public ?string $approvedAt,
        public ?string $approverId,
        public ?string $createdAt,
    ) {
    }
}
