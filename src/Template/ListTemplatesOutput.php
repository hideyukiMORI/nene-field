<?php

declare(strict_types=1);

namespace NeneField\Template;

final readonly class ListTemplatesOutput
{
    /**
     * @param list<ReportTemplate> $items
     */
    public function __construct(
        public array $items,
    ) {
    }
}
