<?php

declare(strict_types=1);

namespace NeneField\Export;

use Nene2\Export\CsvWriter;
use NeneField\Report\ReportExportRow;

/**
 * Renders report export rows as a UTF-8 CSV string with a leading BOM, so Excel
 * (the common SMB consumer) opens Japanese text without mojibake.
 *
 * Cell rendering is delegated to {@see CsvWriter} (NENE2, ADR 0015): it keeps the
 * UTF-8 BOM and RFC 4180 quoting this export already used, and additionally
 * neutralises spreadsheet formula injection in string cells by default — so
 * user-controlled text such as `user_name` or `title` can no longer smuggle an
 * executable formula (e.g. `=cmd|'/c calc'!A1`) into the downloaded CSV.
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

        // BOM on + formula-injection neutralisation on (CsvWriter defaults).
        $writer = new CsvWriter($stream, self::HEADER);
        $writer->writeAll(array_map(
            static fn (ReportExportRow $row): array => [
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
            ],
            $rows,
        ));

        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        return is_string($csv) ? $csv : '';
    }
}
