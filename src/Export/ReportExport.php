<?php

declare(strict_types=1);

namespace NeneField\Export;

/**
 * The result of a report CSV export: the rendered document and the number of
 * rows it contains (the row count is recorded in the audit trail; the rows
 * themselves are not).
 */
final readonly class ReportExport
{
    public function __construct(
        public string $csv,
        public int $rowCount,
    ) {
    }
}
