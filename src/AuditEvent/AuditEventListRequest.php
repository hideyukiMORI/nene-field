<?php

declare(strict_types=1);

namespace NeneField\AuditEvent;

/**
 * Builds an {@see AuditEventFilter} from the `GET /audit-events` query string.
 * Every filter is optional; `limit`/`offset` are parsed/validated upstream by
 * {@see \Nene2\Http\PaginationQueryParser} and passed in.
 */
final readonly class AuditEventListRequest
{
    /**
     * @param array<string, mixed> $query
     */
    public static function toFilter(array $query, int $limit, int $offset): AuditEventFilter
    {
        return new AuditEventFilter(
            entityType: self::str($query, 'entity_type'),
            entityId: self::str($query, 'entity_id'),
            actorId: self::str($query, 'actor_id'),
            eventName: self::str($query, 'event_name'),
            occurredFrom: AuditTimestamp::normalize(self::str($query, 'occurred_from')),
            occurredTo: AuditTimestamp::normalize(self::str($query, 'occurred_to')),
            limit: $limit,
            offset: $offset,
        );
    }

    /**
     * @param array<string, mixed> $query
     */
    private static function str(array $query, string $key): ?string
    {
        $value = $query[$key] ?? null;

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
