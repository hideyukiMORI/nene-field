<?php

declare(strict_types=1);

namespace NeneField\Report;

/**
 * Public JSON presenter for a {@see ReportSummary} (OpenAPI `ReportSummary`).
 */
final readonly class ReportSummaryResponse
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(ReportSummary $summary): array
    {
        return [
            'report_id' => $summary->reportId,
            'user_id' => $summary->userId,
            'user_name' => $summary->userName,
            'title' => $summary->title,
            'work_date' => $summary->workDate,
            'status' => $summary->status->value,
            'tags' => $summary->tags,
            'project_code' => $summary->projectCode,
            'ai_summary' => $summary->aiSummary,
            'submitted_at' => $summary->submittedAt,
            'created_at' => $summary->createdAt,
        ];
    }
}
