<?php

declare(strict_types=1);

namespace NeneField\AuditEvent;

final readonly class ListAuditEventsUseCase implements ListAuditEventsUseCaseInterface
{
    public function __construct(
        private AuditEventRepositoryInterface $events,
    ) {
    }

    public function execute(string $organizationId, AuditEventFilter $filter): ListAuditEventsOutput
    {
        return new ListAuditEventsOutput(
            items: $this->events->search($organizationId, $filter),
            total: $this->events->count($organizationId, $filter),
            limit: $filter->limit,
            offset: $filter->offset,
        );
    }
}
