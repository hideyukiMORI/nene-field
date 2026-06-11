<?php

declare(strict_types=1);

namespace NeneField\Organization;

use Closure;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\Http\ClockInterface;
use NeneField\AuditEvent\AuditRecorderInterface;

final readonly class UpdateOrganizationUseCase implements UpdateOrganizationUseCaseInterface
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

    public function execute(UpdateOrganizationInput $input): Organization
    {
        $existing = $this->organizations->findById($input->organizationId);

        if ($existing === null) {
            throw new OrganizationNotFoundException();
        }

        // Admin-editable settings (null = field not provided → keep existing).
        $name = $input->name ?? $existing->name;
        $aiSummaryEnabled = $input->aiSummaryEnabled ?? $existing->aiSummaryEnabled;
        $notificationEmail = $input->notificationEmailProvided ? $input->notificationEmail : $existing->notificationEmail;
        $webhookUrl = $input->webhookUrlProvided ? $input->webhookUrl : $existing->webhookUrl;

        // Superadmin-only tenant fields; ignored entirely for a tenant admin.
        $slug = $existing->slug;
        $customDomain = $existing->customDomain;
        $isActive = $existing->isActive;

        if ($input->isSuperadmin) {
            $slug = $input->slug ?? $existing->slug;
            $customDomain = $input->customDomainProvided ? $input->customDomain : $existing->customDomain;
            $isActive = $input->isActive ?? $existing->isActive;

            if ($slug !== $existing->slug) {
                $clash = $this->organizations->findBySlug($slug);
                if ($clash !== null && $clash->organizationId !== $existing->organizationId) {
                    throw new OrganizationSlugConflictException();
                }
            }

            if ($customDomain !== null && $customDomain !== $existing->customDomain) {
                $clash = $this->organizations->findByCustomDomain($customDomain);
                if ($clash !== null && $clash->organizationId !== $existing->organizationId) {
                    throw new OrganizationSlugConflictException();
                }
            }
        }

        $updated = new Organization(
            organizationId: $existing->organizationId,
            name: $name,
            slug: $slug,
            isActive: $isActive,
            customDomain: $customDomain,
            aiSummaryEnabled: $aiSummaryEnabled,
            notificationEmail: $notificationEmail,
            webhookUrl: $webhookUrl,
            createdAt: $existing->createdAt,
            updatedAt: $this->clock->now()->format('Y-m-d H:i:s'),
        );

        $this->tx->transactional(function (DatabaseQueryExecutorInterface $exec) use ($existing, $updated, $input): void {
            $this->organizations->update($exec, $updated);
            ($this->auditFactory)($exec)->record(
                $input->actorId,
                $updated->organizationId,
                'organization.updated',
                'Organization',
                $updated->organizationId,
                OrganizationResponse::toArray($existing),
                OrganizationResponse::toArray($updated),
            );
        });

        return $updated;
    }
}
