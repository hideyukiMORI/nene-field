<?php

declare(strict_types=1);

namespace NeneField\AuditEvent;

/**
 * The result of an audit-event CSV export: the rendered document and its row
 * count (the count is recorded in the `audit.exported` event; the rows are not).
 */
final readonly class AuditEventExport
{
    public function __construct(
        public string $csv,
        public int $rowCount,
    ) {
    }
}
