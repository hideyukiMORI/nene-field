<?php

declare(strict_types=1);

namespace NeneField\Report;

use Closure;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use NeneField\AuditEvent\AuditRecorderInterface;

final readonly class DeleteReportUseCase implements DeleteReportUseCaseInterface
{
    /**
     * @param Closure(DatabaseQueryExecutorInterface): AuditRecorderInterface $auditFactory
     */
    public function __construct(
        private ReportRepositoryInterface $reports,
        private DatabaseTransactionManagerInterface $tx,
        private Closure $auditFactory,
    ) {
    }

    public function execute(string $organizationId, string $actorId, string $reportId): void
    {
        $existing = $this->reports->findById($organizationId, $reportId);

        if ($existing === null || $existing->userId !== $actorId) {
            throw new ReportNotFoundException();
        }

        // Only a draft can be deleted; submitted/approved/rejected are retained.
        if ($existing->status !== ReportStatus::Draft) {
            throw new ReportNotEditableException('Only a draft report can be deleted.');
        }

        $this->tx->transactional(function (DatabaseQueryExecutorInterface $exec) use ($existing, $organizationId, $actorId, $reportId): void {
            $this->reports->delete($exec, $organizationId, $reportId);
            ($this->auditFactory)($exec)->record(
                $actorId,
                $organizationId,
                'report.deleted',
                'Report',
                $reportId,
                ReportResponse::toArray($existing),
                null,
            );
        });
    }
}
