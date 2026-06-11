<?php

declare(strict_types=1);

namespace NeneField\AuditEvent;

use Nene2\Database\DatabaseQueryExecutorInterface;

final readonly class PdoAuditEventRepository implements AuditEventRepositoryInterface
{
    private const SELECT = 'SELECT a.event_id, a.organization_id, a.actor_id, u.name AS actor_name, a.event_name,
            a.entity_type, a.entity_id, a.before_json, a.after_json, a.request_id, a.occurred_at
        FROM audit_events a
        LEFT JOIN users u ON u.organization_id = a.organization_id AND u.user_id = a.actor_id';

    /** Safety cap on exported rows to bound memory/response size. */
    private const EXPORT_CAP = 100000;

    public function __construct(
        private DatabaseQueryExecutorInterface $query,
    ) {
    }

    public function search(string $organizationId, AuditEventFilter $filter): array
    {
        [$where, $params] = self::conditions(
            $organizationId,
            $filter->entityType,
            $filter->entityId,
            $filter->actorId,
            $filter->eventName,
            $filter->occurredFrom,
            $filter->occurredTo,
        );
        $params[] = $filter->limit;
        $params[] = $filter->offset;

        $rows = $this->query->fetchAll(
            self::SELECT . ' WHERE ' . $where . ' ORDER BY a.occurred_at DESC, a.event_id DESC LIMIT ? OFFSET ?',
            $params,
        );

        return array_map(static fn (array $row): AuditEvent => self::hydrate($row), $rows);
    }

    public function count(string $organizationId, AuditEventFilter $filter): int
    {
        [$where, $params] = self::conditions(
            $organizationId,
            $filter->entityType,
            $filter->entityId,
            $filter->actorId,
            $filter->eventName,
            $filter->occurredFrom,
            $filter->occurredTo,
        );

        $row = $this->query->fetchOne('SELECT COUNT(*) AS c FROM audit_events a WHERE ' . $where, $params);

        return $row !== null ? (int) $row['c'] : 0;
    }

    public function exportRows(string $organizationId, AuditEventExportFilter $filter): array
    {
        [$where, $params] = self::conditions(
            $organizationId,
            $filter->entityType,
            null,
            null,
            null,
            $filter->occurredFrom,
            $filter->occurredTo,
        );
        $params[] = self::EXPORT_CAP;

        $rows = $this->query->fetchAll(
            self::SELECT . ' WHERE ' . $where . ' ORDER BY a.occurred_at ASC, a.event_id ASC LIMIT ?',
            $params,
        );

        return array_map(static fn (array $row): AuditEvent => self::hydrate($row), $rows);
    }

    /**
     * @return array{0: string, 1: list<mixed>}
     */
    private static function conditions(
        string $organizationId,
        ?string $entityType,
        ?string $entityId,
        ?string $actorId,
        ?string $eventName,
        ?string $occurredFrom,
        ?string $occurredTo,
    ): array {
        $where = ['a.organization_id = ?'];
        $params = [$organizationId];

        if ($entityType !== null) {
            $where[] = 'a.entity_type = ?';
            $params[] = $entityType;
        }
        if ($entityId !== null) {
            $where[] = 'a.entity_id = ?';
            $params[] = $entityId;
        }
        if ($actorId !== null) {
            $where[] = 'a.actor_id = ?';
            $params[] = $actorId;
        }
        if ($eventName !== null) {
            $where[] = 'a.event_name = ?';
            $params[] = $eventName;
        }
        if ($occurredFrom !== null) {
            $where[] = 'a.occurred_at >= ?';
            $params[] = $occurredFrom;
        }
        if ($occurredTo !== null) {
            $where[] = 'a.occurred_at <= ?';
            $params[] = $occurredTo;
        }

        return [implode(' AND ', $where), $params];
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function hydrate(array $row): AuditEvent
    {
        return new AuditEvent(
            eventId: (string) $row['event_id'],
            organizationId: (string) $row['organization_id'],
            actorId: $row['actor_id'] !== null ? (string) $row['actor_id'] : null,
            actorName: $row['actor_name'] !== null ? (string) $row['actor_name'] : null,
            eventName: (string) $row['event_name'],
            entityType: (string) $row['entity_type'],
            entityId: (string) $row['entity_id'],
            before: self::decode($row['before_json']),
            after: self::decode($row['after_json']),
            requestId: $row['request_id'] !== null ? (string) $row['request_id'] : null,
            occurredAt: (string) $row['occurred_at'],
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function decode(mixed $json): ?array
    {
        if (!is_string($json) || $json === '') {
            return null;
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : null;
    }
}
