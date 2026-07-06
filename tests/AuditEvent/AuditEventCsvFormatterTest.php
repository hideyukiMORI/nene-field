<?php

declare(strict_types=1);

namespace NeneField\Tests\AuditEvent;

use NeneField\AuditEvent\AuditEvent;
use NeneField\AuditEvent\AuditEventCsvFormatter;
use PHPUnit\Framework\TestCase;

/**
 * The audit-event CSV formatter: UTF-8 BOM, header, column order, JSON snapshot
 * rendering, RFC-4180 escaping, and formula-injection neutralisation of
 * user-controlled cells (CsvWriter default, ADR 0015).
 */
final class AuditEventCsvFormatterTest extends TestCase
{
    private const BOM = "\xEF\xBB\xBF";

    public function test_empty_export_has_bom_and_header_only(): void
    {
        $csv = (new AuditEventCsvFormatter())->format([]);

        self::assertStringStartsWith(self::BOM, $csv);
        $records = self::parse($csv);
        self::assertCount(1, $records);
        self::assertSame('event_id', $records[0][0]);
        self::assertSame('actor_name', $records[0][6]);
        self::assertSame('after', $records[0][9]);
    }

    public function test_row_values_and_snapshots_round_trip(): void
    {
        $event = new AuditEvent(
            eventId: 'e1',
            organizationId: 'org-1',
            actorId: 'u-1',
            actorName: '管理者',
            eventName: 'report.approved',
            entityType: 'Report',
            entityId: 'r-1',
            before: ['status' => 'submitted'],
            after: ['status' => 'approved', 'note' => 'ok, "done"'],
            requestId: 'req-1',
            occurredAt: '2026-06-11 09:00:00',
        );

        $record = self::parse((new AuditEventCsvFormatter())->format([$event]))[1];

        self::assertSame('e1', $record[0]);
        self::assertSame('2026-06-11 09:00:00', $record[1]);
        self::assertSame('report.approved', $record[2]);
        self::assertSame('管理者', $record[6]);
        self::assertSame('req-1', $record[7]);
        self::assertSame('{"status":"submitted"}', $record[8]);
        self::assertSame('{"status":"approved","note":"ok, \\"done\\""}', $record[9]);
    }

    public function test_null_actor_and_snapshots_render_as_empty(): void
    {
        $event = new AuditEvent(
            eventId: 'e2',
            organizationId: 'org-1',
            actorId: null,
            actorName: null,
            eventName: 'system.purge',
            entityType: 'Report',
            entityId: 'r-2',
            before: null,
            after: null,
            requestId: null,
            occurredAt: '2026-06-11 10:00:00',
        );

        $record = self::parse((new AuditEventCsvFormatter())->format([$event]))[1];

        self::assertSame('', $record[5], 'actor_id');
        self::assertSame('', $record[6], 'actor_name');
        self::assertSame('', $record[7], 'request_id');
        self::assertSame('', $record[8], 'before');
        self::assertSame('', $record[9], 'after');
    }

    /**
     * A formula planted in the user-controlled actor name is neutralised with a
     * leading apostrophe, so it renders as text rather than executing when the
     * export is opened in Excel / LibreOffice / Google Sheets.
     */
    public function test_formula_injection_in_actor_name_is_neutralised(): void
    {
        $event = new AuditEvent(
            eventId: 'e3',
            organizationId: 'org-1',
            actorId: 'u-1',
            actorName: '=cmd|\'/c calc\'!A1',
            eventName: 'user.updated',
            entityType: 'User',
            entityId: 'u-1',
            before: null,
            after: null,
            requestId: null,
            occurredAt: '2026-06-11 11:00:00',
        );

        $record = self::parse((new AuditEventCsvFormatter())->format([$event]))[1];

        self::assertSame('\'=cmd|\'/c calc\'!A1', $record[6], 'actor_name');
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
}
