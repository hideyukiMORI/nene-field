<?php

declare(strict_types=1);

namespace NeneField\AuditEvent;

interface ListAuditEventsUseCaseInterface
{
    public function execute(string $organizationId, AuditEventFilter $filter): ListAuditEventsOutput;
}
