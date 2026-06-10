<?php

declare(strict_types=1);

namespace NeneField\Report;

use Closure;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\Http\ClockInterface;
use NeneField\AuditEvent\AuditRecorderInterface;

final readonly class UpdateReportUseCase implements UpdateReportUseCaseInterface
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

    public function execute(UpdateReportInput $input): Report
    {
        $existing = $this->reports->findById($input->organizationId, $input->reportId);

        // Non-owner (or missing) is a 404; only the submitter edits their own report.
        if ($existing === null || $existing->userId !== $input->actorId) {
            throw new ReportNotFoundException();
        }

        if (!$existing->status->isEditable()) {
            throw new ReportNotEditableException();
        }

        $updated = $existing->withEditedContent(
            title: $input->title,
            body: $input->body,
            workDate: $input->workDate,
            tags: $input->tags,
            templateId: $input->templateId,
            projectCode: $input->projectCode,
            invoiceWorkOrderId: $input->invoiceWorkOrderId,
            recordsEntityId: $input->recordsEntityId,
            updatedAt: $this->clock->now()->format('Y-m-d H:i:s'),
        );

        $this->tx->transactional(function (DatabaseQueryExecutorInterface $exec) use ($existing, $updated, $input): void {
            $this->reports->update($exec, $updated);
            ($this->auditFactory)($exec)->record(
                $input->actorId,
                $input->organizationId,
                'report.edited',
                'Report',
                $updated->reportId,
                ReportResponse::toArray($existing),
                ReportResponse::toArray($updated),
            );
        });

        return $updated;
    }
}
