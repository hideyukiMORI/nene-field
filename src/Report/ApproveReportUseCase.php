<?php

declare(strict_types=1);

namespace NeneField\Report;

use Closure;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\Http\ClockInterface;
use NeneField\AuditEvent\AuditRecorderInterface;

final readonly class ApproveReportUseCase implements ApproveReportUseCaseInterface
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

    public function execute(ApproveReportInput $input): Report
    {
        $existing = $this->reports->findById($input->organizationId, $input->reportId);

        if ($existing === null) {
            throw new ReportNotFoundException();
        }

        if ($existing->status !== ReportStatus::Submitted) {
            throw new ReportNotInSubmittedStateException();
        }

        $approved = $existing->withApproved(
            $input->actorId,
            $input->comment,
            $this->clock->now()->format('Y-m-d H:i:s'),
        );

        $this->tx->transactional(function (DatabaseQueryExecutorInterface $exec) use ($existing, $approved, $input): void {
            $this->reports->update($exec, $approved);
            ($this->auditFactory)($exec)->record(
                $input->actorId,
                $input->organizationId,
                'report.approved',
                'Report',
                $approved->reportId,
                ReportResponse::toArray($existing),
                ReportResponse::toArray($approved),
            );
        });

        return $approved;
    }
}
