<?php

declare(strict_types=1);

namespace NeneField\Organization;

final readonly class GetOrganizationUseCase implements GetOrganizationUseCaseInterface
{
    public function __construct(
        private OrganizationRepositoryInterface $organizations,
    ) {
    }

    public function execute(string $organizationId): ?Organization
    {
        return $this->organizations->findById($organizationId);
    }
}
