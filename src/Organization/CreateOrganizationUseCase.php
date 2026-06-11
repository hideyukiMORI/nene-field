<?php

declare(strict_types=1);

namespace NeneField\Organization;

use Closure;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\Http\ClockInterface;
use NeneField\AuditEvent\AuditRecorderInterface;
use NeneField\Support\Uuid;

final readonly class CreateOrganizationUseCase implements CreateOrganizationUseCaseInterface
{
    /**
     * @param Closure(DatabaseQueryExecutorInterface): AuditRecorderInterface $auditFactory
     */
    public function __construct(
        private OrganizationRepositoryInterface $organizations,
        private DatabaseTransactionManagerInterface $tx,
        private Closure $auditFactory,
        private ClockInterface $clock,
    ) {
    }

    public function execute(CreateOrganizationInput $input): Organization
    {
        if ($this->organizations->findBySlug($input->slug) !== null) {
            throw new OrganizationSlugConflictException();
        }

        if ($input->customDomain !== null && $this->organizations->findByCustomDomain($input->customDomain) !== null) {
            throw new OrganizationSlugConflictException();
        }

        $now = $this->clock->now()->format('Y-m-d H:i:s');

        $organization = new Organization(
            organizationId: Uuid::v4(),
            name: $input->name,
            slug: $input->slug,
            isActive: $input->isActive,
            customDomain: $input->customDomain,
            aiSummaryEnabled: false,
            notificationEmail: null,
            webhookUrl: null,
            createdAt: $now,
            updatedAt: $now,
        );

        $this->tx->transactional(function (DatabaseQueryExecutorInterface $exec) use ($organization, $input): void {
            $this->organizations->insert($exec, $organization);
            ($this->auditFactory)($exec)->record(
                $input->actorId,
                $organization->organizationId,
                'organization.created',
                'Organization',
                $organization->organizationId,
                null,
                OrganizationResponse::toArray($organization),
            );
        });

        return $organization;
    }
}
