<?php

declare(strict_types=1);

namespace NeneField\Report;

use NeneField\Auth\Role;

final readonly class ListReportsUseCase implements ListReportsUseCaseInterface
{
    public function __construct(
        private ReportRepositoryInterface $reports,
    ) {
    }

    public function execute(string $organizationId, string $actorId, Role $actorRole, ReportFilter $filter): ListReportsOutput
    {
        // Submitters only ever see their own reports, regardless of any user_id
        // query param; approver/admin see the whole organization.
        $effective = $actorRole->canApprove() ? $filter : $filter->scopedToUser($actorId);

        return new ListReportsOutput(
            items: $this->reports->search($organizationId, $effective),
            total: $this->reports->count($organizationId, $effective),
            limit: $effective->limit,
            offset: $effective->offset,
        );
    }
}
