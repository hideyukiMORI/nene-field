<?php

declare(strict_types=1);

namespace NeneField\Report;

interface UpdateReportUseCaseInterface
{
    /**
     * @throws ReportNotFoundException when the report is absent or not owned by the actor.
     * @throws ReportNotEditableException when the report is not in an editable state.
     */
    public function execute(UpdateReportInput $input): Report;
}
