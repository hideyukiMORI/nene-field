<?php

declare(strict_types=1);

namespace NeneField\Tests\AuditEvent;

use NeneField\AuditEvent\AuditTimestamp;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Normalization of client timestamp filters to the stored `occurred_at` shape.
 */
final class AuditTimestampTest extends TestCase
{
    #[DataProvider('cases')]
    public function test_normalize(?string $input, ?string $expected): void
    {
        self::assertSame($expected, AuditTimestamp::normalize($input));
    }

    /**
     * @return iterable<string, array{?string, ?string}>
     */
    public static function cases(): iterable
    {
        yield 'null' => [null, null];
        yield 'empty' => ['', null];
        yield 'whitespace' => ['   ', null];
        yield 'bare date' => ['2026-06-01', '2026-06-01'];
        yield 'iso utc' => ['2026-06-01T09:00:00Z', '2026-06-01 09:00:00'];
        yield 'iso offset' => ['2026-06-01T09:00:00+09:00', '2026-06-01 09:00:00'];
        yield 'iso offset compact' => ['2026-06-01T09:00:00+0900', '2026-06-01 09:00:00'];
        yield 'space separated' => ['2026-06-01 09:00:00', '2026-06-01 09:00:00'];
        yield 'surrounding spaces' => ['  2026-06-01T00:00:00Z  ', '2026-06-01 00:00:00'];
    }
}
