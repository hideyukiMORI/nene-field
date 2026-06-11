<?php

declare(strict_types=1);

namespace NeneField\Organization;

interface UpdateOrganizationUseCaseInterface
{
    /**
     * @throws OrganizationNotFoundException     when the organization does not exist
     * @throws OrganizationSlugConflictException when the new slug or custom domain is taken
     */
    public function execute(UpdateOrganizationInput $input): Organization;
}
