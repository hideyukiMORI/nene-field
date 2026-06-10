<?php

declare(strict_types=1);

namespace NeneField\Report;

use Closure;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\Http\ClockInterface;
use NeneField\AuditEvent\AuditRecorderInterface;
use NeneField\Support\Uuid;

final readonly class CreateReportUseCase implements CreateReportUseCaseInterface
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

    public function execute(CreateReportInput $input): Report
    {
        $now = $this->clock->now()->format('Y-m-d H:i:s');

        $report = new Report(
            reportId: Uuid::v4(),
            organizationId: $input->organizationId,
            userId: $input->actorId,
            title: $input->title,
            body: $input->body,
            workDate: $input->workDate,
            status: ReportStatus::Draft,
            tags: $input->tags,
            templateId: $input->templateId,
            projectCode: $input->projectCode,
            invoiceWorkOrderId: $input->invoiceWorkOrderId,
            recordsEntityId: $input->recordsEntityId,
            createdAt: $now,
            updatedAt: $now,
        );

        $this->tx->transactional(function (DatabaseQueryExecutorInterface $exec) use ($report, $input): void {
            $this->reports->insert($exec, $report);
            ($this->auditFactory)($exec)->record(
                $input->actorId,
                $input->organizationId,
                'report.created',
                'Report',
                $report->reportId,
                null,
                ReportResponse::toArray($report),
            );
        });

        return $report;
    }
}
