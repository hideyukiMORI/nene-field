<?php

declare(strict_types=1);

namespace NeneField\User;

use Nene2\Database\DatabaseQueryExecutorInterface;
use NeneField\Auth\Role;

final readonly class PdoUserRepository implements UserRepositoryInterface
{
    private const COLUMNS = 'user_id, organization_id, name, email, password_hash, role, is_active, created_at, updated_at';

    public function __construct(
        private DatabaseQueryExecutorInterface $query,
    ) {
    }

    public function findByEmailInOrg(string $organizationId, string $email): ?User
    {
        $row = $this->query->fetchOne(
            'SELECT ' . self::COLUMNS . ' FROM users WHERE organization_id = ? AND email = ?',
            [$organizationId, $email],
        );

        return $row !== null ? self::hydrate($row) : null;
    }

    public function findById(string $organizationId, string $userId): ?User
    {
        $row = $this->query->fetchOne(
            'SELECT ' . self::COLUMNS . ' FROM users WHERE organization_id = ? AND user_id = ?',
            [$organizationId, $userId],
        );

        return $row !== null ? self::hydrate($row) : null;
    }

    public function updatePasswordHash(string $organizationId, string $userId, string $passwordHash, string $now): void
    {
        $this->query->execute(
            'UPDATE users SET password_hash = ?, updated_at = ? WHERE organization_id = ? AND user_id = ?',
            [$passwordHash, $now, $organizationId, $userId],
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function hydrate(array $row): User
    {
        return new User(
            userId: (string) $row['user_id'],
            organizationId: (string) $row['organization_id'],
            name: (string) $row['name'],
            email: (string) $row['email'],
            passwordHash: (string) $row['password_hash'],
            role: Role::from((string) $row['role']),
            isActive: (bool) $row['is_active'],
            createdAt: $row['created_at'] !== null ? (string) $row['created_at'] : null,
            updatedAt: $row['updated_at'] !== null ? (string) $row['updated_at'] : null,
        );
    }
}
