<?php

declare(strict_types=1);

namespace NeneField\Report;

use Closure;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\Http\ClockInterface;
use NeneField\AuditEvent\AuditRecorderInterface;

final readonly class SubmitReportUseCase implements SubmitReportUseCaseInterface
{
    /**
     * @param Closure(DatabaseQueryExecutorInterface): AuditRecorderInterface $auditFactory
     */
    public function __construct(
        private ReportRepositoryInterface $reports,
        private DatabaseTransactionManagerInterface $tx,
        private Closure $auditFactory,
        private ClockInterface $clock,
    ) {
    }

    public function execute(string $organizationId, string $actorId, string $reportId): Report
    {
        $existing = $this->reports->findById($organizationId, $reportId);

        if ($existing === null || $existing->userId !== $actorId) {
            throw new ReportNotFoundException();
        }

        if (!$existing->status->isSubmittable()) {
            throw new ReportNotEditableException('The report cannot be submitted in its current state.');
        }

        $submitted = $existing->withSubmitted($this->clock->now()->format('Y-m-d H:i:s'));

        $this->tx->transactional(function (DatabaseQueryExecutorInterface $exec) use ($existing, $submitted, $organizationId, $actorId): void {
            $this->reports->update($exec, $submitted);
            ($this->auditFactory)($exec)->record(
                $actorId,
                $organizationId,
                'report.submitted',
                'Report',
                $submitted->reportId,
                ReportResponse::toArray($existing),
                ReportResponse::toArray($submitted),
            );
        });

        return $submitted;
    }
}
