<?php

declare(strict_types=1);

namespace NeneField\Export;

use NeneField\Report\ReportExportRow;

/**
 * Renders report export rows as a UTF-8 CSV string with a leading BOM, so Excel
 * (the common SMB consumer) opens Japanese text without mojibake.
 */
final readonly class ReportCsvFormatter
{
    private const BOM = "\xEF\xBB\xBF";

    /** @var list<string> */
    private const HEADER = [
        'report_id', 'work_date', 'user_id', 'user_name', 'title', 'status',
        'project_code', 'tags', 'submitted_at', 'approved_at', 'approver_id', 'created_at',
    ];

    /**
     * @param list<ReportExportRow> $rows
     */
    public function format(array $rows): string
    {
        $stream = fopen('php://temp', 'r+');

        if ($stream === false) {
            // Extremely unlikely; fall back to a header-only document.
            return self::BOM . implode(',', self::HEADER) . "\r\n";
        }

        fputcsv($stream, self::HEADER, ',', '"', '');

        foreach ($rows as $row) {
            fputcsv($stream, [
                $row->reportId,
                $row->workDate,
                $row->userId,
                $row->userName,
                $row->title,
                $row->status->value,
                $row->projectCode ?? '',
                implode('|', $row->tags),
                $row->submittedAt ?? '',
                $row->approvedAt ?? '',
                $row->approverId ?? '',
                $row->createdAt ?? '',
            ], ',', '"', '');
        }

        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        return self::BOM . (is_string($csv) ? $csv : '');
    }
}
