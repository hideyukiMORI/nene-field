<?php

declare(strict_types=1);

namespace NeneField\Report;

/**
 * Filter + pagination for listing reports. `tags` filtering is not yet supported
 * (documented OpenAPI param, deferred — needs JSON/tag-table support).
 */
final readonly class ReportFilter
{
    public const SORTS = ['work_date_desc', 'work_date_asc', 'submitted_at_desc'];

    /**
     * @param list<ReportStatus> $statuses
     */
    public function __construct(
        public ?string $userId = null,
        public ?string $workDateFrom = null,
        public ?string $workDateTo = null,
        public array $statuses = [],
        public ?string $projectCode = null,
        public string $sort = 'work_date_desc',
        public int $limit = 20,
        public int $offset = 0,
    ) {
    }

    /** Returns a copy scoped to a single submitter (enforced visibility). */
    public function scopedToUser(string $userId): self
    {
        return new self(
            userId: $userId,
            workDateFrom: $this->workDateFrom,
            workDateTo: $this->workDateTo,
            statuses: $this->statuses,
            projectCode: $this->projectCode,
            sort: $this->sort,
            limit: $this->limit,
            offset: $this->offset,
        );
    }
}
