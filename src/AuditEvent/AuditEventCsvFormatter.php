<?php

declare(strict_types=1);

namespace NeneField\AuditEvent;

use Nene2\Export\CsvWriter;

/**
 * Renders audit events as a UTF-8 CSV string with a leading BOM. `before` /
 * `after` are emitted as their JSON text (already sanitized at write time).
 *
 * Cell rendering is delegated to {@see CsvWriter} (NENE2, ADR 0015): it keeps the
 * UTF-8 BOM and RFC 4180 quoting the fleet expects, and additionally neutralises
 * spreadsheet formula injection in string cells by default — so an attacker who
 * plants a value like `=cmd|'/c calc'!A1` in `actor_name` can no longer have it
 * executed when the export is opened in Excel / LibreOffice / Google Sheets.
 */
final readonly class AuditEventCsvFormatter
{
    private const BOM = "\xEF\xBB\xBF";

    /** @var list<string> */
    private const HEADER = [
        'event_id', 'occurred_at', 'event_name', 'entity_type', 'entity_id',
        'actor_id', 'actor_name', 'request_id', 'before', 'after',
    ];

    /**
     * @param list<AuditEvent> $events
     */
    public function format(array $events): string
    {
        $stream = fopen('php://temp', 'r+');

        if ($stream === false) {
            return self::BOM . implode(',', self::HEADER) . "\r\n";
        }

        // BOM on + formula-injection neutralisation on (CsvWriter defaults).
        $writer = new CsvWriter($stream, self::HEADER);
        $writer->writeAll(array_map(
            static fn (AuditEvent $event): array => [
                $event->eventId,
                $event->occurredAt,
                $event->eventName,
                $event->entityType,
                $event->entityId,
                $event->actorId ?? '',
                $event->actorName ?? '',
                $event->requestId ?? '',
                self::json($event->before),
                self::json($event->after),
            ],
            $events,
        ));

        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        return is_string($csv) ? $csv : '';
    }

    /**
     * @param array<string, mixed>|null $data
     */
    private static function json(?array $data): string
    {
        if ($data === null) {
            return '';
        }

        $encoded = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $encoded !== false ? $encoded : '';
    }
}
