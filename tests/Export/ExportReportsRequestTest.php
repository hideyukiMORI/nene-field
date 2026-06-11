<?php

declare(strict_types=1);

namespace NeneField\Tests\Export;

use NeneField\Export\ExportReportsRequest;
use NeneField\Report\ReportStatus;
use PHPUnit\Framework\TestCase;

/**
 * Boundary-value validation for the `GET /export/csv` query parser. The work-date
 * range is mandatory and ISO-formatted; `status` defaults to `[approved]`.
 */
final class ExportReportsRequestTest extends TestCase
{
    public function test_valid_minimal_defaults_to_approved(): void
    {
        $request = ExportReportsRequest::parse(['work_date_from' => '2026-06-01', 'work_date_to' => '2026-06-30']);

        self::assertSame([], $request->errors);
        self::assertSame([ReportStatus::Approved], $request->statuses);
        self::assertNull($request->userId);
        self::assertNull($request->projectCode);
    }

    public function test_dates_are_required(): void
    {
        $request = ExportReportsRequest::parse([]);
        self::assertSame('required', self::codeFor($request->errors, 'work_date_from'));
        self::assertSame('required', self::codeFor($request->errors, 'work_date_to'));
    }

    public function test_date_format_is_validated(): void
    {
        $request = ExportReportsRequest::parse(['work_date_from' => '2026/06/01', 'work_date_to' => 'soon']);
        self::assertSame('invalid_format', self::codeFor($request->errors, 'work_date_from'));
        self::assertSame('invalid_format', self::codeFor($request->errors, 'work_date_to'));
    }

    public function test_inverted_range_is_rejected(): void
    {
        $request = ExportReportsRequest::parse(['work_date_from' => '2026-06-30', 'work_date_to' => '2026-06-01']);
        self::assertSame('invalid_range', self::codeFor($request->errors, 'work_date_to'));
    }

    public function test_equal_range_is_allowed(): void
    {
        $request = ExportReportsRequest::parse(['work_date_from' => '2026-06-10', 'work_date_to' => '2026-06-10']);
        self::assertSame([], $request->errors);
    }

    public function test_explicit_statuses_are_parsed_and_unknowns_ignored(): void
    {
        $request = ExportReportsRequest::parse([
            'work_date_from' => '2026-06-01',
            'work_date_to' => '2026-06-30',
            'status' => ['approved', 'submitted', 'bogus'],
        ]);

        self::assertSame([ReportStatus::Approved, ReportStatus::Submitted], $request->statuses);
    }

    public function test_single_status_string_is_accepted(): void
    {
        $request = ExportReportsRequest::parse([
            'work_date_from' => '2026-06-01',
            'work_date_to' => '2026-06-30',
            'status' => 'rejected',
        ]);

        self::assertSame([ReportStatus::Rejected], $request->statuses);
    }

    public function test_all_invalid_statuses_fall_back_to_default(): void
    {
        $request = ExportReportsRequest::parse([
            'work_date_from' => '2026-06-01',
            'work_date_to' => '2026-06-30',
            'status' => ['nope'],
        ]);

        self::assertSame([ReportStatus::Approved], $request->statuses);
    }

    public function test_optional_filters_are_captured(): void
    {
        $request = ExportReportsRequest::parse([
            'work_date_from' => '2026-06-01',
            'work_date_to' => '2026-06-30',
            'user_id' => 'u-1',
            'project_code' => '  PRJ-9  ',
        ]);

        self::assertSame('u-1', $request->userId);
        self::assertSame('PRJ-9', $request->projectCode);
    }

    /**
     * @param list<array{field: string, message: string, code: string}> $errors
     */
    private static function codeFor(array $errors, string $field): ?string
    {
        foreach ($errors as $error) {
            if ($error['field'] === $field) {
                return $error['code'];
            }
        }

        return null;
    }
}
