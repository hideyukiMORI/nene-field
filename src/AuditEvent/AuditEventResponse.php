<?php

declare(strict_types=1);

namespace NeneField\AuditEvent;

/**
 * Public JSON presenter for an {@see AuditEvent} (OpenAPI `AuditEventResponse`).
 * `before` / `after` are returned as their decoded objects; the snapshots were
 * already sanitized at write time (no secrets).
 */
final readonly class AuditEventResponse
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(AuditEvent $event): array
    {
        return [
            'event_id' => $event->eventId,
            'organization_id' => $event->organizationId,
            'entity_type' => $event->entityType,
            'entity_id' => $event->entityId,
            'event_name' => $event->eventName,
            'actor_id' => $event->actorId,
            'actor_name' => $event->actorName,
            'before' => $event->before,
            'after' => $event->after,
            'request_id' => $event->requestId,
            'occurred_at' => $event->occurredAt,
        ];
    }
}
