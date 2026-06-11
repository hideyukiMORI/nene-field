<?php

declare(strict_types=1);

namespace NeneField\AuditEvent;

interface ExportAuditEventsUseCaseInterface
{
    public function execute(string $organizationId, ?string $actorId, AuditEventExportFilter $filter): AuditEventExport;
}
