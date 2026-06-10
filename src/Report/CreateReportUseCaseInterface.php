<?php

declare(strict_types=1);

namespace NeneField\Report;

interface CreateReportUseCaseInterface
{
    public function execute(CreateReportInput $input): Report;
}
