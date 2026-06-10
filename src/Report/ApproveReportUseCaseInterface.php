<?php

declare(strict_types=1);

namespace NeneField\Report;

interface ApproveReportUseCaseInterface
{
    /**
     * @throws ReportNotFoundException when the report is absent in the org.
     * @throws ReportNotInSubmittedStateException when the report is not awaiting approval.
     */
    public function execute(ApproveReportInput $input): Report;
}
