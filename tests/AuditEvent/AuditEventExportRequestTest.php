<?php

declare(strict_types=1);

namespace NeneField\Tests\AuditEvent;

use NeneField\AuditEvent\AuditEventExportRequest;
use PHPUnit\Framework\TestCase;

/**
 * Boundary-value validation for the `GET /audit-events/export` query parser. The
 * occurred-at range is mandatory and normalized; `entity_type` is optional.
 */
final class AuditEventExportRequestTest extends TestCase
{
    public function test_valid(): void
    {
        $request = AuditEventExportRequest::parse([
            'occurred_from' => '2026-06-01T00:00:00Z',
            'occurred_to' => '2026-06-30T23:59:59Z',
            'entity_type' => 'Report',
        ]);

        self::assertSame([], $request->errors);
        self::assertSame('2026-06-01 00:00:00', $request->occurredFrom);
        self::assertSame('2026-06-30 23:59:59', $request->occurredTo);
        self::assertSame('Report', $request->entityType);
    }

    public function test_occurred_range_is_required(): void
    {
        $request = AuditEventExportRequest::parse([]);
        self::assertSame('required', self::codeFor($request->errors, 'occurred_from'));
        self::assertSame('required', self::codeFor($request->errors, 'occurred_to'));
    }

    public function test_inverted_range_is_rejected(): void
    {
        $request = AuditEventExportRequest::parse([
            'occurred_from' => '2026-06-30',
            'occurred_to' => '2026-06-01',
        ]);
        self::assertSame('invalid_range', self::codeFor($request->errors, 'occurred_to'));
    }

    public function test_entity_type_is_optional(): void
    {
        $request = AuditEventExportRequest::parse(['occurred_from' => '2026-06-01', 'occurred_to' => '2026-06-30']);
        self::assertSame([], $request->errors);
        self::assertNull($request->entityType);
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
