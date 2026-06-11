<?php

declare(strict_types=1);

namespace NeneField\AuditEvent;

/**
 * Filter + pagination for listing audit events. `occurredFrom` / `occurredTo` are
 * normalized timestamps (`Y-m-d H:i:s` prefix) compared against `occurred_at`.
 */
final readonly class AuditEventFilter
{
    public function __construct(
        public ?string $entityType = null,
        public ?string $entityId = null,
        public ?string $actorId = null,
        public ?string $eventName = null,
        public ?string $occurredFrom = null,
        public ?string $occurredTo = null,
        public int $limit = 20,
        public int $offset = 0,
    ) {
    }
}
