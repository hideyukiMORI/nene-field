<?php

declare(strict_types=1);

namespace NeneField\AuditEvent;

/**
 * Criteria for an audit-event CSV export. The occurred-at range is mandatory
 * (bounded export); `entityType` optionally narrows it. No pagination.
 */
final readonly class AuditEventExportFilter
{
    public function __construct(
        public string $occurredFrom,
        public string $occurredTo,
        public ?string $entityType = null,
    ) {
    }
}
