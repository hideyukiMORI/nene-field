<?php

declare(strict_types=1);

namespace NeneField\Organization;

use Nene2\Database\DatabaseQueryExecutorInterface;

/**
 * Data-access contract for the tenant root.
 *
 * The `organizations` table is NOT tenant-scoped (it defines the tenants), so
 * these lookups intentionally do not filter by `organization_id`. Tenant-scoped
 * repositories (reports, users, …) will filter by the resolved org id.
 *
 * `listAll` / `insert` / `update` back the superadmin provisioning + admin
 * settings endpoints; mutations take the transaction-bound executor so the write
 * and its audit row commit in the same transaction (ADR 0014). The secret AI
 * columns (`ai_api_url` / `ai_api_key`) are intentionally never read or written
 * here (legal-compliance.md §5).
 */
interface OrganizationRepositoryInterface
{
    public function findById(string $organizationId): ?Organization;

    public function findBySlug(string $slug): ?Organization;

    public function findByCustomDomain(string $customDomain): ?Organization;

    /**
     * @return list<Organization>
     */
    public function listAll(int $limit, int $offset): array;

    public function countAll(): int;

    public function insert(DatabaseQueryExecutorInterface $executor, Organization $organization): void;

    public function update(DatabaseQueryExecutorInterface $executor, Organization $organization): void;
}
