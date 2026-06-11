<?php

declare(strict_types=1);

namespace NeneField\User;

use Nene2\Database\DatabaseQueryExecutorInterface;

/**
 * Tenant-scoped data access for users. Every lookup is scoped by
 * `organization_id` (multi-tenancy.md §4); email uniqueness is per organization.
 *
 * Mutations (`insert` / `update` / `delete`) take the transaction-bound executor
 * so the write and its audit row commit in the same transaction (ADR 0014).
 */
interface UserRepositoryInterface
{
    public function findByEmailInOrg(string $organizationId, string $email): ?User;

    public function findById(string $organizationId, string $userId): ?User;

    public function updatePasswordHash(string $organizationId, string $userId, string $passwordHash, string $now): void;

    /**
     * @return list<User>
     */
    public function listByOrg(string $organizationId, int $limit, int $offset): array;

    public function countByOrg(string $organizationId): int;

    public function insert(DatabaseQueryExecutorInterface $executor, User $user): void;

    public function update(DatabaseQueryExecutorInterface $executor, User $user): void;

    public function delete(DatabaseQueryExecutorInterface $executor, string $organizationId, string $userId): void;
}
