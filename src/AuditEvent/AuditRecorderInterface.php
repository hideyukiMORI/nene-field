<?php

declare(strict_types=1);

namespace NeneField\AuditEvent;

/**
 * Records one immutable audit event (ADR 0014 / audit-logging.md).
 *
 * Obtained per-transaction via a `Closure(DatabaseQueryExecutorInterface):
 * AuditRecorderInterface` factory, so the audit row is written with the **same**
 * executor — and therefore the same DB transaction — as the mutation.
 *
 * `before` / `after` are **sanitized** snapshots (e.g. `*Response::toArray()`);
 * secrets, tokens, and raw bytes must never be passed in.
 */
interface AuditRecorderInterface
{
    /**
     * @param array<string, mixed>|null $before  Sanitized state before (null for create).
     * @param array<string, mixed>|null $after   Sanitized state after (null for delete).
     */
    public function record(
        ?string $actorId,
        string $organizationId,
        string $eventName,
        string $entityType,
        string $entityId,
        ?array $before,
        ?array $after,
    ): void;
}
