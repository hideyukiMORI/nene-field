<?php

declare(strict_types=1);

namespace NeneField\Organization;

interface GetOrganizationUseCaseInterface
{
    public function execute(string $organizationId): ?Organization;
}
