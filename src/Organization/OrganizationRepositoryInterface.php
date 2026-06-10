<?php

declare(strict_types=1);

namespace NeneField\Organization;

/**
 * Data-access contract for the tenant root.
 *
 * The `organizations` table is NOT tenant-scoped (it defines the tenants), so
 * these lookups intentionally do not filter by `organization_id`. Tenant-scoped
 * repositories (reports, users, …) will filter by the resolved org id.
 */
interface OrganizationRepositoryInterface
{
    public function findById(string $organizationId): ?Organization;

    public function findBySlug(string $slug): ?Organization;

    public function findByCustomDomain(string $customDomain): ?Organization;
}
