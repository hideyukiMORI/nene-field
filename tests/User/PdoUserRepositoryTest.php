<?php

declare(strict_types=1);

namespace NeneField\Tests\User;

use Nene2\Config\DatabaseConfig;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use NeneField\User\PdoUserRepository;
use PHPUnit\Framework\TestCase;

final class PdoUserRepositoryTest extends TestCase
{
    private PdoDatabaseQueryExecutor $executor;
    private PdoUserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $factory = new PdoConnectionFactory(DatabaseConfig::sqlite(':memory:', 'test'));
        $this->executor = new PdoDatabaseQueryExecutor($factory);

        $this->executor->execute(
            'CREATE TABLE users (
                user_id CHAR(36) NOT NULL PRIMARY KEY,
                organization_id CHAR(36) NOT NULL,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                role VARCHAR(20) NOT NULL,
                is_active INTEGER NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL
            )',
        );

        // Same email in two different organizations (per-tenant uniqueness).
        $this->insert('u-a', 'org-a', 'shared@example.com', 'submitter');
        $this->insert('u-b', 'org-b', 'shared@example.com', 'admin');

        $this->repository = new PdoUserRepository($this->executor);
    }

    public function test_find_by_email_is_scoped_per_org(): void
    {
        $a = $this->repository->findByEmailInOrg('org-a', 'shared@example.com');
        $b = $this->repository->findByEmailInOrg('org-b', 'shared@example.com');

        self::assertNotNull($a);
        self::assertNotNull($b);
        self::assertSame('u-a', $a->userId);
        self::assertSame('u-b', $b->userId);
        self::assertSame('submitter', $a->role->value);
        self::assertSame('admin', $b->role->value);
    }

    public function test_find_by_id_is_scoped_per_org(): void
    {
        self::assertNotNull($this->repository->findById('org-a', 'u-a'));
        // Right user id, wrong org → not found (no cross-tenant leak).
        self::assertNull($this->repository->findById('org-b', 'u-a'));
    }

    public function test_update_password_hash_targets_one_tenant_user(): void
    {
        $this->repository->updatePasswordHash('org-a', 'u-a', 'new-hash', '2026-06-11 00:00:00');

        $a = $this->repository->findById('org-a', 'u-a');
        $b = $this->repository->findById('org-b', 'u-b');
        self::assertNotNull($a);
        self::assertNotNull($b);
        self::assertSame('new-hash', $a->passwordHash);
        self::assertNotSame('new-hash', $b->passwordHash);
    }

    private function insert(string $userId, string $orgId, string $email, string $role): void
    {
        $this->executor->execute(
            'INSERT INTO users (user_id, organization_id, name, email, password_hash, role, is_active, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$userId, $orgId, 'Name', $email, 'hash-' . $userId, $role, 1, '2026-06-11 00:00:00', '2026-06-11 00:00:00'],
        );
    }
}
