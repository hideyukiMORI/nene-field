<?php

declare(strict_types=1);

namespace NeneField\Tests\Organization;

use Closure;
use DateTimeImmutable;
use Nene2\Config\DatabaseConfig;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use Nene2\Database\PdoDatabaseTransactionManager;
use NeneField\AuditEvent\AuditRecorder;
use NeneField\AuditEvent\AuditRecorderInterface;
use NeneField\Organization\CreateOrganizationInput;
use NeneField\Organization\CreateOrganizationUseCase;
use NeneField\Organization\ListOrganizationsUseCase;
use NeneField\Organization\OrganizationNotFoundException;
use NeneField\Organization\OrganizationSlugConflictException;
use NeneField\Organization\PdoOrganizationRepository;
use NeneField\Organization\UpdateOrganizationInput;
use NeneField\Organization\UpdateOrganizationUseCase;
use NeneField\Tests\Support\FixedClock;
use PHPUnit\Framework\TestCase;

/**
 * End-to-end organization management against file SQLite. Covers superadmin
 * provisioning + admin settings, slug uniqueness, role-gated tenant fields, the
 * AI-secret preservation invariant, and same-transaction `organization.*` audit.
 */
final class OrganizationManagementTest extends TestCase
{
    private const ACTOR = 'superadmin-1';

    private string $dbPath;
    private PdoConnectionFactory $factory;
    private PdoOrganizationRepository $organizations;
    private FixedClock $clock;
    /** @var Closure(DatabaseQueryExecutorInterface): AuditRecorderInterface */
    private Closure $auditFactory;
    private PdoDatabaseTransactionManager $tx;

    protected function setUp(): void
    {
        parent::setUp();

        $tmp = tempnam(sys_get_temp_dir(), 'nf_org_');
        self::assertIsString($tmp);
        $this->dbPath = $tmp;

        $this->factory = new PdoConnectionFactory(DatabaseConfig::sqlite($this->dbPath, 'test'));
        $setup = new PdoDatabaseQueryExecutor($this->factory);
        $setup->execute(OrganizationSchema::CREATE_ORGANIZATIONS_TABLE);
        $setup->execute(OrganizationSchema::CREATE_AUDIT_TABLE);

        $this->organizations = new PdoOrganizationRepository(new PdoDatabaseQueryExecutor($this->factory));
        $this->clock = new FixedClock(new DateTimeImmutable('2026-06-11T08:00:00Z'));
        $clock = $this->clock;
        $this->auditFactory = static fn (DatabaseQueryExecutorInterface $exec): AuditRecorderInterface
            => new AuditRecorder($exec, $clock);
        $this->tx = new PdoDatabaseTransactionManager($this->factory);
    }

    protected function tearDown(): void
    {
        @unlink($this->dbPath);
        parent::tearDown();
    }

    public function test_create_then_list_and_audit(): void
    {
        $created = $this->createUseCase()->execute(new CreateOrganizationInput(
            actorId: self::ACTOR,
            name: '山田造園',
            slug: 'yamada',
            customDomain: null,
            isActive: true,
        ));
        self::assertSame('yamada', $created->slug);
        self::assertSame(1, $this->auditCount('organization.created', $created->organizationId));

        $output = (new ListOrganizationsUseCase($this->organizations))->execute(20, 0);
        self::assertSame(1, $output->total);
        self::assertSame('yamada', $output->items[0]->slug);
    }

    public function test_duplicate_slug_is_rejected(): void
    {
        $this->createUseCase()->execute(new CreateOrganizationInput(self::ACTOR, 'A', 'acme', null, true));

        $this->expectException(OrganizationSlugConflictException::class);
        $this->createUseCase()->execute(new CreateOrganizationInput(self::ACTOR, 'B', 'acme', null, true));
    }

    public function test_admin_update_ignores_superadmin_fields_and_preserves_ai_secret(): void
    {
        $id = $this->seedOrgWithSecret('acme', 'sk-secret-123');

        $updated = $this->updateUseCase()->execute(new UpdateOrganizationInput(
            organizationId: $id,
            actorId: 'admin-1',
            isSuperadmin: false,
            name: '新名称',
            aiSummaryEnabled: true,
            notificationEmailProvided: true,
            notificationEmail: 'ops@example.com',
            webhookUrlProvided: false,
            webhookUrl: null,
            slug: 'hacked',          // superadmin-only — must be ignored
            customDomainProvided: false,
            customDomain: null,
            isActive: false,         // superadmin-only — must be ignored
        ));

        self::assertSame('新名称', $updated->name);
        self::assertTrue($updated->aiSummaryEnabled);
        self::assertSame('ops@example.com', $updated->notificationEmail);
        self::assertSame('acme', $updated->slug, 'admin must not change slug');
        self::assertTrue($updated->isActive, 'admin must not change is_active');
        self::assertSame('sk-secret-123', $this->rawAiApiKey($id), 'settings update must not touch the AI secret');
        self::assertSame(1, $this->auditCount('organization.updated', $id));
    }

    public function test_superadmin_update_can_change_slug(): void
    {
        $created = $this->createUseCase()->execute(new CreateOrganizationInput(self::ACTOR, 'A', 'acme', null, true));

        $updated = $this->updateUseCase()->execute(new UpdateOrganizationInput(
            organizationId: $created->organizationId,
            actorId: self::ACTOR,
            isSuperadmin: true,
            name: null,
            aiSummaryEnabled: null,
            notificationEmailProvided: false,
            notificationEmail: null,
            webhookUrlProvided: false,
            webhookUrl: null,
            slug: 'acme-renamed',
            customDomainProvided: false,
            customDomain: null,
            isActive: false,
        ));

        self::assertSame('acme-renamed', $updated->slug);
        self::assertFalse($updated->isActive);
    }

    public function test_update_missing_organization_is_not_found(): void
    {
        $this->expectException(OrganizationNotFoundException::class);
        $this->updateUseCase()->execute(new UpdateOrganizationInput(
            organizationId: 'ghost',
            actorId: self::ACTOR,
            isSuperadmin: true,
            name: 'X',
            aiSummaryEnabled: null,
            notificationEmailProvided: false,
            notificationEmail: null,
            webhookUrlProvided: false,
            webhookUrl: null,
            slug: null,
            customDomainProvided: false,
            customDomain: null,
            isActive: null,
        ));
    }

    public function test_superadmin_slug_change_to_existing_is_rejected(): void
    {
        $this->createUseCase()->execute(new CreateOrganizationInput(self::ACTOR, 'A', 'taken', null, true));
        $b = $this->createUseCase()->execute(new CreateOrganizationInput(self::ACTOR, 'B', 'free', null, true));

        $this->expectException(OrganizationSlugConflictException::class);
        $this->updateUseCase()->execute(new UpdateOrganizationInput(
            organizationId: $b->organizationId,
            actorId: self::ACTOR,
            isSuperadmin: true,
            name: null,
            aiSummaryEnabled: null,
            notificationEmailProvided: false,
            notificationEmail: null,
            webhookUrlProvided: false,
            webhookUrl: null,
            slug: 'taken',
            customDomainProvided: false,
            customDomain: null,
            isActive: null,
        ));
    }

    private function createUseCase(): CreateOrganizationUseCase
    {
        return new CreateOrganizationUseCase($this->organizations, $this->tx, $this->auditFactory, $this->clock);
    }

    private function updateUseCase(): UpdateOrganizationUseCase
    {
        return new UpdateOrganizationUseCase($this->organizations, $this->tx, $this->auditFactory, $this->clock);
    }

    private function seedOrgWithSecret(string $slug, string $aiApiKey): string
    {
        $id = 'org-' . $slug;
        $write = new PdoDatabaseQueryExecutor($this->factory);
        $write->execute(
            'INSERT INTO organizations
                (organization_id, name, slug, custom_domain, is_active, ai_summary_enabled,
                 ai_api_url, ai_api_key, notification_email, webhook_url, created_at, updated_at)
             VALUES (?, ?, ?, NULL, 1, 0, NULL, ?, NULL, NULL, ?, ?)',
            [$id, '旧名称', $slug, $aiApiKey, '2026-06-01 00:00:00', '2026-06-01 00:00:00'],
        );

        return $id;
    }

    private function rawAiApiKey(string $organizationId): ?string
    {
        $read = new PdoDatabaseQueryExecutor($this->factory);
        $row = $read->fetchOne('SELECT ai_api_key FROM organizations WHERE organization_id = ?', [$organizationId]);

        return $row !== null && $row['ai_api_key'] !== null ? (string) $row['ai_api_key'] : null;
    }

    private function auditCount(string $eventName, string $entityId): int
    {
        $read = new PdoDatabaseQueryExecutor($this->factory);
        $row = $read->fetchOne(
            'SELECT COUNT(*) AS c FROM audit_events WHERE event_name = ? AND entity_id = ?',
            [$eventName, $entityId],
        );

        return $row !== null ? (int) $row['c'] : -1;
    }
}
