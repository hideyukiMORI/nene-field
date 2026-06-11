<?php

declare(strict_types=1);

namespace NeneField\AuditEvent;

/**
 * Builds an {@see AuditEventFilter} from the `GET /audit-events` query string.
 * Every filter is optional; `limit`/`offset` are clamped to the shared bounds.
 */
final readonly class AuditEventListRequest
{
    private const MAX_LIMIT = 100;
    private const DEFAULT_LIMIT = 20;

    /**
     * @param array<string, mixed> $query
     */
    public static function toFilter(array $query): AuditEventFilter
    {
        return new AuditEventFilter(
            entityType: self::str($query, 'entity_type'),
            entityId: self::str($query, 'entity_id'),
            actorId: self::str($query, 'actor_id'),
            eventName: self::str($query, 'event_name'),
            occurredFrom: AuditTimestamp::normalize(self::str($query, 'occurred_from')),
            occurredTo: AuditTimestamp::normalize(self::str($query, 'occurred_to')),
            limit: self::intParam($query['limit'] ?? null, self::DEFAULT_LIMIT, 1, self::MAX_LIMIT),
            offset: self::intParam($query['offset'] ?? null, 0, 0, PHP_INT_MAX),
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

    private static function intParam(mixed $raw, int $default, int $min, int $max): int
    {
        if (!is_string($raw) && !is_int($raw)) {
            return $default;
        }

        return max($min, min($max, (int) $raw));
    }
}
