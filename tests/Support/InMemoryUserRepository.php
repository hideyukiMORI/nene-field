<?php

declare(strict_types=1);

namespace NeneField\Tests\Support;

use Nene2\Database\DatabaseQueryExecutorInterface;
use NeneField\User\User;
use NeneField\User\UserRepositoryInterface;

/**
 * In-memory {@see UserRepositoryInterface} for use-case tests (no database).
 */
final class InMemoryUserRepository implements UserRepositoryInterface
{
    /** @var array<string, User> keyed by user id */
    private array $users = [];

    /**
     * @param list<User> $users
     */
    public function __construct(array $users = [])
    {
        foreach ($users as $user) {
            $this->users[$user->userId] = $user;
        }
    }

    public function findByEmailInOrg(string $organizationId, string $email): ?User
    {
        foreach ($this->users as $user) {
            if ($user->organizationId === $organizationId && $user->email === $email) {
                return $user;
            }
        }

        return null;
    }

    public function findById(string $organizationId, string $userId): ?User
    {
        $user = $this->users[$userId] ?? null;

        return ($user !== null && $user->organizationId === $organizationId) ? $user : null;
    }

    public function updatePasswordHash(string $organizationId, string $userId, string $passwordHash, string $now): void
    {
        $user = $this->findById($organizationId, $userId);

        if ($user === null) {
            return;
        }

        $this->users[$userId] = new User(
            userId: $user->userId,
            organizationId: $user->organizationId,
            name: $user->name,
            email: $user->email,
            passwordHash: $passwordHash,
            role: $user->role,
            isActive: $user->isActive,
            createdAt: $user->createdAt,
            updatedAt: $now,
        );
    }

    public function listByOrg(string $organizationId, int $limit, int $offset): array
    {
        $matches = array_values(array_filter(
            $this->users,
            static fn (User $u): bool => $u->organizationId === $organizationId,
        ));

        return array_slice($matches, $offset, $limit);
    }

    public function countByOrg(string $organizationId): int
    {
        return count(array_filter(
            $this->users,
            static fn (User $u): bool => $u->organizationId === $organizationId,
        ));
    }

    public function insert(DatabaseQueryExecutorInterface $executor, User $user): void
    {
        $this->users[$user->userId] = $user;
    }

    public function update(DatabaseQueryExecutorInterface $executor, User $user): void
    {
        $this->users[$user->userId] = $user;
    }

    public function delete(DatabaseQueryExecutorInterface $executor, string $organizationId, string $userId): void
    {
        $user = $this->findById($organizationId, $userId);

        if ($user !== null) {
            unset($this->users[$userId]);
        }
    }
}
