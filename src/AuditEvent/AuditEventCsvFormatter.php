<?php

declare(strict_types=1);

namespace NeneField\AuditEvent;

/**
 * Renders audit events as a UTF-8 CSV string with a leading BOM. `before` /
 * `after` are emitted as their JSON text (already sanitized at write time).
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

        fputcsv($stream, self::HEADER, ',', '"', '');

        foreach ($events as $event) {
            fputcsv($stream, [
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
            ], ',', '"', '');
        }

        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        return self::BOM . (is_string($csv) ? $csv : '');
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
