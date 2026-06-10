<?php

declare(strict_types=1);

namespace NeneField\Report;

final readonly class ListReportsOutput
{
    /**
     * @param list<ReportSummary> $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $limit,
        public int $offset,
    ) {
    }
}
