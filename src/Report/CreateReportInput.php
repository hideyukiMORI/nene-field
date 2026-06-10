<?php

declare(strict_types=1);

namespace NeneField\Report;

final readonly class CreateReportInput
{
    /**
     * @param list<string> $tags
     */
    public function __construct(
        public string $organizationId,
        public string $actorId,
        public string $title,
        public string $body,
        public string $workDate,
        public array $tags = [],
        public ?string $templateId = null,
        public ?string $projectCode = null,
        public ?string $invoiceWorkOrderId = null,
        public ?string $recordsEntityId = null,
    ) {
    }
}
