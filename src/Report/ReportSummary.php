<?php

declare(strict_types=1);

namespace NeneField\Report;

/**
 * Condensed report for list views (OpenAPI `ReportSummary`). `userName` is joined
 * from the users table at query time.
 */
final readonly class ReportSummary
{
    /**
     * @param list<string> $tags
     */
    public function __construct(
        public string $reportId,
        public string $userId,
        public string $userName,
        public string $title,
        public string $workDate,
        public ReportStatus $status,
        public array $tags,
        public ?string $projectCode,
        public ?string $aiSummary,
        public ?string $submittedAt,
        public ?string $createdAt,
    ) {
    }
}
