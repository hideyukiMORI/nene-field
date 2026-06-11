<?php

declare(strict_types=1);

namespace NeneField\AuditEvent;

use Closure;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;

/**
 * Builds an audit-event CSV export and records that the export happened
 * (`audit.exported`). The audit captures the filter criteria + row count only —
 * never the exported rows.
 */
final readonly class ExportAuditEventsUseCase implements ExportAuditEventsUseCaseInterface
{
    /**
     * @param Closure(DatabaseQueryExecutorInterface): AuditRecorderInterface $auditFactory
     */
    public function __construct(
        private AuditEventRepositoryInterface $events,
        private AuditEventCsvFormatter $formatter,
        private DatabaseTransactionManagerInterface $tx,
        private Closure $auditFactory,
    ) {
    }

    public function execute(string $organizationId, ?string $actorId, AuditEventExportFilter $filter): AuditEventExport
    {
        $rows = $this->events->exportRows($organizationId, $filter);
        $csv = $this->formatter->format($rows);
        $rowCount = count($rows);

        $this->tx->transactional(function (DatabaseQueryExecutorInterface $exec) use ($organizationId, $actorId, $filter, $rowCount): void {
            ($this->auditFactory)($exec)->record(
                $actorId,
                $organizationId,
                'audit.exported',
                'AuditEvent',
                $organizationId,
                null,
                [
                    'occurred_from' => $filter->occurredFrom,
                    'occurred_to' => $filter->occurredTo,
                    'entity_type' => $filter->entityType,
                    'row_count' => $rowCount,
                ],
            );
        });

        return new AuditEventExport($csv, $rowCount);
    }
}
