<?php

declare(strict_types=1);

namespace NeneField\Report;

interface RejectReportUseCaseInterface
{
    /**
     * @throws ReportNotFoundException when the report is absent in the org.
     * @throws ReportNotInSubmittedStateException when the report is not awaiting approval.
     */
    public function execute(RejectReportInput $input): Report;
}
