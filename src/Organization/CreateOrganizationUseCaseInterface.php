<?php

declare(strict_types=1);

namespace NeneField\Organization;

interface CreateOrganizationUseCaseInterface
{
    /**
     * @throws OrganizationSlugConflictException when the slug or custom domain is taken
     */
    public function execute(CreateOrganizationInput $input): Organization;
}
