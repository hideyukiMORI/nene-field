<?php

declare(strict_types=1);

namespace NeneField\Report;

use NeneField\Auth\Role;

final readonly class GetReportUseCase implements GetReportUseCaseInterface
{
    public function __construct(
        private ReportRepositoryInterface $reports,
    ) {
    }

    public function execute(string $organizationId, string $reportId, string $actorId, Role $actorRole): ?Report
    {
        $report = $this->reports->findById($organizationId, $reportId);

        if ($report === null) {
            return null;
        }

        // Approver/admin/superadmin may read any report in the org; a submitter
        // may read only their own (non-owner access is indistinguishable from 404).
        if ($actorRole->canApprove() || $report->userId === $actorId) {
            return $report;
        }

        return null;
    }
}
