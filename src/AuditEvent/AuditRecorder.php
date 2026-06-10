<?php

declare(strict_types=1);

namespace NeneField\AuditEvent;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Http\ClockInterface;
use NeneField\Support\Uuid;

/**
 * Inserts an append-only audit row using the provided executor. Construct with
 * the transaction-bound executor inside `DatabaseTransactionManager::transactional()`
 * so the audit write is atomic with the mutation (ADR 0014).
 */
final readonly class AuditRecorder implements AuditRecorderInterface
{
    public function __construct(
        private DatabaseQueryExecutorInterface $executor,
        private ClockInterface $clock,
        private ?string $requestId = null,
    ) {
    }

    public function record(
        ?string $actorId,
        string $organizationId,
        string $eventName,
        string $entityType,
        string $entityId,
        ?array $before,
        ?array $after,
    ): void {
        $this->executor->execute(
            'INSERT INTO audit_events
                (event_id, organization_id, actor_id, event_name, entity_type, entity_id,
                 before_json, after_json, request_id, occurred_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                Uuid::v4(),
                $organizationId,
                $actorId,
                $eventName,
                $entityType,
                $entityId,
                self::encode($before),
                self::encode($after),
                $this->requestId,
                $this->clock->now()->format('Y-m-d H:i:s'),
            ],
        );
    }

    /**
     * @param array<string, mixed>|null $data
     */
    private static function encode(?array $data): ?string
    {
        return $data === null
            ? null
            : json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }
}
