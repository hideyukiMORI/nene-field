<?php

declare(strict_types=1);

namespace NeneField\AuditEvent;

/**
 * A read model of one immutable audit row (terms.md §1: `AuditEvent`). `before`
 * / `after` are the decoded sanitized snapshots stored at write time; secrets are
 * never present. `actorName` is resolved from the users join (null for system or
 * deleted actors).
 */
final readonly class AuditEvent
{
    /**
     * @param array<string, mixed>|null $before
     * @param array<string, mixed>|null $after
     */
    public function __construct(
        public string $eventId,
        public string $organizationId,
        public ?string $actorId,
        public ?string $actorName,
        public string $eventName,
        public string $entityType,
        public string $entityId,
        public ?array $before,
        public ?array $after,
        public ?string $requestId,
        public string $occurredAt,
    ) {
    }
}
