<?php

declare(strict_types=1);

namespace NeneField\Tests\Export;

use NeneField\Export\ReportCsvFormatter;
use NeneField\Report\ReportExportRow;
use NeneField\Report\ReportStatus;
use PHPUnit\Framework\TestCase;

/**
 * The CSV formatter: UTF-8 BOM, header, value rendering, and RFC-4180 escaping of
 * fields containing commas, quotes, and newlines.
 */
final class ReportCsvFormatterTest extends TestCase
{
    private const BOM = "\xEF\xBB\xBF";

    public function test_empty_export_has_bom_and_header_only(): void
    {
        $csv = (new ReportCsvFormatter())->format([]);

        self::assertStringStartsWith(self::BOM, $csv);
        $records = self::parse($csv);
        self::assertCount(1, $records);
        self::assertSame('report_id', $records[0][0]);
        self::assertSame('created_at', $records[0][11]);
    }

    public function test_row_values_are_rendered_in_order(): void
    {
        $records = self::parse((new ReportCsvFormatter())->format([self::row()]));

        self::assertCount(2, $records);
        self::assertSame('r-1', $records[1][0]);
        self::assertSame('2026-06-11', $records[1][1]);
        self::assertSame('田中太郎', $records[1][3]);
        self::assertSame('approved', $records[1][5]);
        self::assertSame('tag-a|tag-b', $records[1][7]);
    }

    public function test_nulls_render_as_empty_strings(): void
    {
        $row = new ReportExportRow('r-2', '2026-06-11', 'u-1', '', 'No project', ReportStatus::Draft, null, [], null, null, null, null);
        $record = self::parse((new ReportCsvFormatter())->format([$row]))[1];

        self::assertSame('', $record[6], 'project_code');
        self::assertSame('', $record[7], 'tags');
        self::assertSame('', $record[8], 'submitted_at');
    }

    public function test_special_characters_round_trip(): void
    {
        $row = new ReportExportRow('r-3', '2026-06-11', 'u-1', 'Doe, John', "line1\nline2 \"q\"", ReportStatus::Approved, 'P,1', [], null, null, null, null);
        $record = self::parse((new ReportCsvFormatter())->format([$row]))[1];

        self::assertSame('Doe, John', $record[3]);
        self::assertSame("line1\nline2 \"q\"", $record[4]);
        self::assertSame('P,1', $record[6]);
    }

    /**
     * User-controlled text that would parse as a spreadsheet formula is
     * neutralised with a leading apostrophe (CsvWriter default, ADR 0015), so the
     * value renders as text instead of executing when the CSV is opened in Excel.
     */
    public function test_formula_injection_in_user_text_is_neutralised(): void
    {
        $row = new ReportExportRow(
            'r-4',
            '2026-06-11',
            'u-1',
            '=cmd|\'/c calc\'!A1',
            '@SUM(1+1)',
            ReportStatus::Draft,
            '-1+2',
            [],
            null,
            null,
            null,
            null,
        );
        $record = self::parse((new ReportCsvFormatter())->format([$row]))[1];

        self::assertSame('\'=cmd|\'/c calc\'!A1', $record[3], 'user_name');
        self::assertSame('\'@SUM(1+1)', $record[4], 'title');
        self::assertSame('\'-1+2', $record[6], 'project_code');
    }

    /**
     * Parses the CSV (minus BOM) into records via a stream, so quoted newlines
     * are handled correctly.
     *
     * @return list<list<string>>
     */
    private static function parse(string $csv): array
    {
        $body = substr($csv, strlen(self::BOM));
        $stream = fopen('php://temp', 'r+');
        self::assertIsResource($stream);
        fwrite($stream, $body);
        rewind($stream);

        $records = [];
        while (($row = fgetcsv($stream, 0, ',', '"', '')) !== false) {
            $records[] = array_map(static fn (mixed $v): string => (string) $v, $row);
        }
        fclose($stream);

        return $records;
    }

    private static function row(): ReportExportRow
    {
        return new ReportExportRow(
            reportId: 'r-1',
            workDate: '2026-06-11',
            userId: 'u-1',
            userName: '田中太郎',
            title: '現場A 報告',
            status: ReportStatus::Approved,
            projectCode: 'PRJ-1',
            tags: ['tag-a', 'tag-b'],
            submittedAt: '2026-06-11 09:00:00',
            approvedAt: '2026-06-11 10:00:00',
            approverId: 'mgr-1',
            createdAt: '2026-06-11 08:00:00',
        );
    }
}
