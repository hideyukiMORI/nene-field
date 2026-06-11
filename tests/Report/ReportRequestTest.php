<?php

declare(strict_types=1);

namespace NeneField\Tests\Report;

use NeneField\Report\ReportRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Boundary-value validation for the shared report create/update request parser.
 * Title (200) and body (10000) length limits are checked at the edge; the work
 * date is a strict `YYYY-MM-DD` format (not a calendar-validity check).
 */
final class ReportRequestTest extends TestCase
{
    public function test_valid_minimal(): void
    {
        $request = ReportRequest::parse(['title' => 'T', 'body' => 'B', 'work_date' => '2026-06-11']);
        self::assertSame([], $request->errors);
        self::assertSame([], $request->tags);
        self::assertNull($request->templateId);
    }

    public function test_required_fields(): void
    {
        $request = ReportRequest::parse([]);
        self::assertSame('required', self::codeFor($request->errors, 'title'));
        self::assertSame('required', self::codeFor($request->errors, 'body'));
        self::assertSame('required', self::codeFor($request->errors, 'work_date'));
    }

    public function test_title_at_max_ok_over_rejected(): void
    {
        self::assertNull(self::codeFor(self::parse(['title' => str_repeat('a', 200)])->errors, 'title'));
        self::assertSame('too_long', self::codeFor(self::parse(['title' => str_repeat('a', 201)])->errors, 'title'));
    }

    public function test_body_at_max_ok_over_rejected(): void
    {
        self::assertNull(self::codeFor(self::parse(['body' => str_repeat('a', 10000)])->errors, 'body'));
        self::assertSame('too_long', self::codeFor(self::parse(['body' => str_repeat('a', 10001)])->errors, 'body'));
    }

    #[DataProvider('invalidDates')]
    public function test_invalid_work_date_format_is_rejected(string $date): void
    {
        self::assertSame('invalid_format', self::codeFor(self::parse(['work_date' => $date])->errors, 'work_date'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidDates(): iterable
    {
        yield 'slashes' => ['2026/06/11'];
        yield 'single digit month' => ['2026-6-11'];
        yield 'no separators' => ['20260611'];
        yield 'text' => ['yesterday'];
        yield 'time appended' => ['2026-06-11T00:00'];
    }

    public function test_well_formed_date_passes_even_if_not_a_real_calendar_day(): void
    {
        // The parser checks shape only; calendar validity is out of scope here.
        self::assertNull(self::codeFor(self::parse(['work_date' => '2026-13-40'])->errors, 'work_date'));
    }

    public function test_tags_filters_non_strings_and_empties(): void
    {
        $request = self::parse(['tags' => ['a', '', 'b', 123, null, 'c']]);
        self::assertSame(['a', 'b', 'c'], $request->tags);
    }

    public function test_tags_non_array_becomes_empty(): void
    {
        self::assertSame([], self::parse(['tags' => 'urgent'])->tags);
    }

    public function test_blank_optional_strings_become_null(): void
    {
        $request = self::parse(['template_id' => '', 'project_code' => '  ', 'invoice_work_order_id' => 'WO-1']);
        self::assertNull($request->templateId);
        self::assertSame('WO-1', $request->invoiceWorkOrderId);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private static function parse(array $overrides): ReportRequest
    {
        return ReportRequest::parse(array_merge(['title' => 'T', 'body' => 'B', 'work_date' => '2026-06-11'], $overrides));
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
