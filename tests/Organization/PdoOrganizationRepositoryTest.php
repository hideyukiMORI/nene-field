<?php

declare(strict_types=1);

namespace NeneField\Tests\Organization;

use Nene2\Config\DatabaseConfig;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use NeneField\Organization\PdoOrganizationRepository;
use NeneField\Support\Uuid;
use PHPUnit\Framework\TestCase;

final class PdoOrganizationRepositoryTest extends TestCase
{
    private PdoDatabaseQueryExecutor $executor;
    private PdoOrganizationRepository $repository;
    private string $orgId;

    protected function setUp(): void
    {
        parent::setUp();

        $factory = new PdoConnectionFactory(DatabaseConfig::sqlite(':memory:', 'test'));
        $this->executor = new PdoDatabaseQueryExecutor($factory);

        $this->executor->execute(
            'CREATE TABLE organizations (
                organization_id CHAR(36) NOT NULL PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                slug VARCHAR(100) NOT NULL,
                custom_domain VARCHAR(255) NULL,
                is_active INTEGER NOT NULL DEFAULT 1,
                ai_summary_enabled INTEGER NOT NULL DEFAULT 0,
                notification_email VARCHAR(255) NULL,
                webhook_url VARCHAR(255) NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL
            )',
        );

        $this->orgId = Uuid::v4();
        $now = '2026-06-11 00:00:00';
        $this->executor->execute(
            'INSERT INTO organizations
                (organization_id, name, slug, custom_domain, is_active, ai_summary_enabled, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$this->orgId, '山田造園', 'yamada', 'yamada.example.com', 1, 0, $now, $now],
        );

        $this->repository = new PdoOrganizationRepository($this->executor);
    }

    public function test_find_by_slug_returns_organization(): void
    {
        $org = $this->repository->findBySlug('yamada');

        self::assertNotNull($org);
        self::assertSame($this->orgId, $org->organizationId);
        self::assertSame('yamada', $org->slug);
        self::assertTrue($org->isActive);
        self::assertSame('yamada.example.com', $org->customDomain);
    }

    public function test_find_by_custom_domain_returns_organization(): void
    {
        $org = $this->repository->findByCustomDomain('yamada.example.com');

        self::assertNotNull($org);
        self::assertSame($this->orgId, $org->organizationId);
    }

    public function test_find_by_id_returns_organization(): void
    {
        $org = $this->repository->findById($this->orgId);

        self::assertNotNull($org);
        self::assertSame('yamada', $org->slug);
    }

    public function test_returns_null_for_unknown_slug(): void
    {
        self::assertNull($this->repository->findBySlug('unknown'));
        self::assertNull($this->repository->findByCustomDomain('nope.example.com'));
        self::assertNull($this->repository->findById(Uuid::v4()));
    }
}
