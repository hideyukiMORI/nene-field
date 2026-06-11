<?php

declare(strict_types=1);

namespace NeneField\Export;

use Closure;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use NeneField\AuditEvent\AuditRecorderInterface;
use NeneField\Report\ReportExportFilter;
use NeneField\Report\ReportRepositoryInterface;
use NeneField\Report\ReportStatus;

/**
 * Builds a report CSV export and records a `report.exported` audit event. Per
 * audit-logging / terms.md §8 the audit captures the **filter criteria and row
 * count only — never the exported rows** (the data itself is not duplicated into
 * the trail).
 */
final readonly class ExportReportsUseCase implements ExportReportsUseCaseInterface
{
    /**
     * @param Closure(DatabaseQueryExecutorInterface): AuditRecorderInterface $auditFactory
     */
    public function __construct(
        private ReportRepositoryInterface $reports,
        private ReportCsvFormatter $formatter,
        private DatabaseTransactionManagerInterface $tx,
        private Closure $auditFactory,
    ) {
    }

    public function execute(string $organizationId, ?string $actorId, ReportExportFilter $filter): ReportExport
    {
        $rows = $this->reports->exportRows($organizationId, $filter);
        $csv = $this->formatter->format($rows);
        $rowCount = count($rows);

        $this->tx->transactional(function (DatabaseQueryExecutorInterface $exec) use ($organizationId, $actorId, $filter, $rowCount): void {
            ($this->auditFactory)($exec)->record(
                $actorId,
                $organizationId,
                'report.exported',
                'Report',
                $organizationId,
                null,
                [
                    'work_date_from' => $filter->workDateFrom,
                    'work_date_to' => $filter->workDateTo,
                    'statuses' => array_map(static fn (ReportStatus $s): string => $s->value, $filter->statuses),
                    'user_id' => $filter->userId,
                    'project_code' => $filter->projectCode,
                    'row_count' => $rowCount,
                ],
            );
        });

        return new ReportExport($csv, $rowCount);
    }
}
