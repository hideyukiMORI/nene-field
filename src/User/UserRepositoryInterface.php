<?php

declare(strict_types=1);

namespace NeneField\User;

/**
 * Tenant-scoped data access for users. Every lookup is scoped by
 * `organization_id` (multi-tenancy.md §4); email uniqueness is per organization.
 */
interface UserRepositoryInterface
{
    public function findByEmailInOrg(string $organizationId, string $email): ?User;

    public function findById(string $organizationId, string $userId): ?User;

    public function updatePasswordHash(string $organizationId, string $userId, string $passwordHash, string $now): void;
}
