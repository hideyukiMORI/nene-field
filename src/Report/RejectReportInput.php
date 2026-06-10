<?php

declare(strict_types=1);

namespace NeneField\Report;

final readonly class RejectReportInput
{
    public function __construct(
        public string $organizationId,
        public string $actorId,
        public string $reportId,
        public string $comment,
    ) {
    }
}
