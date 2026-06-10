<?php

declare(strict_types=1);

namespace NeneField\Report;

interface DeleteReportUseCaseInterface
{
    /**
     * @throws ReportNotFoundException when the report is absent or not owned by the actor.
     * @throws ReportNotEditableException when the report is not a deletable draft.
     */
    public function execute(string $organizationId, string $actorId, string $reportId): void;
}
