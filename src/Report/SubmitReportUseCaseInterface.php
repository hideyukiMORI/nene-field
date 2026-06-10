<?php

declare(strict_types=1);

namespace NeneField\Report;

interface SubmitReportUseCaseInterface
{
    /**
     * @throws ReportNotFoundException when the report is absent or not owned by the actor.
     * @throws ReportNotEditableException when the report is not in a submittable state.
     */
    public function execute(string $organizationId, string $actorId, string $reportId): Report;
}
