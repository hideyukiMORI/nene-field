<?php

declare(strict_types=1);

namespace NeneField\Tests\Template;

use Closure;
use DateTimeImmutable;
use Nene2\Config\DatabaseConfig;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use Nene2\Database\PdoDatabaseTransactionManager;
use NeneField\AuditEvent\AuditRecorder;
use NeneField\AuditEvent\AuditRecorderInterface;
use NeneField\Support\Uuid;
use NeneField\Template\CreateTemplateInput;
use NeneField\Template\CreateTemplateUseCase;
use NeneField\Template\DeleteTemplateUseCase;
use NeneField\Template\GetTemplateUseCase;
use NeneField\Template\ListTemplatesUseCase;
use NeneField\Template\PdoTemplateRepository;
use NeneField\Template\TemplateField;
use NeneField\Template\TemplateFieldType;
use NeneField\Template\TemplateNotFoundException;
use NeneField\Template\UpdateTemplateInput;
use NeneField\Template\UpdateTemplateUseCase;
use NeneField\Tests\Support\FixedClock;
use PHPUnit\Framework\TestCase;

/**
 * End-to-end template management against file SQLite, exercising the real
 * transaction manager + audit recorder: every mutation persists and writes its
 * `template.*` audit event in the same transaction (ADR 0014), and at most one
 * template per org stays the default.
 */
final class TemplateManagementTest extends TestCase
{
    private const ORG = 'org-1';
    private const ADMIN = 'admin-1';

    private string $dbPath;
    private PdoConnectionFactory $factory;
    private PdoTemplateRepository $templates;
    private FixedClock $clock;
    /** @var Closure(DatabaseQueryExecutorInterface): AuditRecorderInterface */
    private Closure $auditFactory;
    private PdoDatabaseTransactionManager $tx;

    protected function setUp(): void
    {
        parent::setUp();

        $tmp = tempnam(sys_get_temp_dir(), 'nf_template_');
        self::assertIsString($tmp);
        $this->dbPath = $tmp;

        $this->factory = new PdoConnectionFactory(DatabaseConfig::sqlite($this->dbPath, 'test'));
        $setup = new PdoDatabaseQueryExecutor($this->factory);
        $setup->execute(TemplateSchema::CREATE_TEMPLATES_TABLE);
        $setup->execute(TemplateSchema::CREATE_AUDIT_TABLE);

        $this->templates = new PdoTemplateRepository(new PdoDatabaseQueryExecutor($this->factory));
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

    public function test_full_lifecycle_persists_and_audits(): void
    {
        $created = $this->createUseCase()->execute(new CreateTemplateInput(
            organizationId: self::ORG,
            actorId: self::ADMIN,
            name: '日報',
            description: '標準テンプレート',
            fields: [
                new TemplateField('summary', '作業内容', TemplateFieldType::Textarea, true),
                new TemplateField('weather', '天候', TemplateFieldType::Select, false, ['晴れ', '雨']),
            ],
            isDefault: true,
        ));
        self::assertTrue($created->isDefault);
        self::assertCount(2, $created->fields);
        self::assertSame(1, $this->auditCount('template.created', $created->templateId));

        // fields round-trip through JSON
        $fetched = (new GetTemplateUseCase($this->templates))->execute(self::ORG, $created->templateId);
        self::assertNotNull($fetched);
        self::assertSame(TemplateFieldType::Select, $fetched->fields[1]->type);
        self::assertSame(['晴れ', '雨'], $fetched->fields[1]->options);

        // list
        $list = (new ListTemplatesUseCase($this->templates))->execute(self::ORG);
        self::assertCount(1, $list->items);

        // update name + fields
        $updated = (new UpdateTemplateUseCase($this->templates, $this->tx, $this->auditFactory, $this->clock))->execute(
            new UpdateTemplateInput(
                self::ORG,
                self::ADMIN,
                $created->templateId,
                name: '日報（改）',
                descriptionProvided: false,
                description: null,
                fields: [new TemplateField('summary', '作業内容', TemplateFieldType::Textarea, true)],
                isDefault: null,
            ),
        );
        self::assertSame('日報（改）', $updated->name);
        self::assertCount(1, $updated->fields);
        self::assertTrue($updated->isDefault, 'isDefault kept when not provided');
        self::assertSame(1, $this->auditCount('template.updated', $created->templateId));

        // delete
        (new DeleteTemplateUseCase($this->templates, $this->tx, $this->auditFactory))->execute(self::ORG, self::ADMIN, $created->templateId);
        self::assertNull($this->templates->findById(self::ORG, $created->templateId));
        self::assertSame(1, $this->auditCount('template.deleted', $created->templateId));
    }

    public function test_only_one_default_per_org(): void
    {
        $first = $this->createUseCase()->execute(new CreateTemplateInput(self::ORG, self::ADMIN, 'A', null, [], true));
        $second = $this->createUseCase()->execute(new CreateTemplateInput(self::ORG, self::ADMIN, 'B', null, [], true));

        $reloadFirst = $this->templates->findById(self::ORG, $first->templateId);
        $reloadSecond = $this->templates->findById(self::ORG, $second->templateId);
        self::assertNotNull($reloadFirst);
        self::assertNotNull($reloadSecond);
        self::assertFalse($reloadFirst->isDefault, 'creating a new default clears the previous one');
        self::assertTrue($reloadSecond->isDefault);
    }

    public function test_update_to_default_clears_other_default(): void
    {
        $first = $this->createUseCase()->execute(new CreateTemplateInput(self::ORG, self::ADMIN, 'A', null, [], true));
        $second = $this->createUseCase()->execute(new CreateTemplateInput(self::ORG, self::ADMIN, 'B', null, [], false));

        (new UpdateTemplateUseCase($this->templates, $this->tx, $this->auditFactory, $this->clock))->execute(
            new UpdateTemplateInput(self::ORG, self::ADMIN, $second->templateId, name: null, descriptionProvided: false, description: null, fields: null, isDefault: true),
        );

        $reloadFirst = $this->templates->findById(self::ORG, $first->templateId);
        self::assertNotNull($reloadFirst);
        self::assertFalse($reloadFirst->isDefault);
    }

    public function test_template_in_another_org_is_not_found_on_update(): void
    {
        $created = $this->createUseCase()->execute(new CreateTemplateInput(self::ORG, self::ADMIN, 'A', null, [], false));

        $this->expectException(TemplateNotFoundException::class);
        (new UpdateTemplateUseCase($this->templates, $this->tx, $this->auditFactory, $this->clock))->execute(
            new UpdateTemplateInput('org-2', self::ADMIN, $created->templateId, name: 'hacked', descriptionProvided: false, description: null, fields: null, isDefault: null),
        );
    }

    public function test_delete_missing_template_is_not_found(): void
    {
        $this->expectException(TemplateNotFoundException::class);
        (new DeleteTemplateUseCase($this->templates, $this->tx, $this->auditFactory))->execute(self::ORG, self::ADMIN, Uuid::v4());
    }

    public function test_list_is_scoped_to_organization(): void
    {
        $this->createUseCase()->execute(new CreateTemplateInput(self::ORG, self::ADMIN, 'A', null, [], false));
        $this->createUseCase()->execute(new CreateTemplateInput('org-2', self::ADMIN, 'B', null, [], false));

        $list = (new ListTemplatesUseCase($this->templates))->execute(self::ORG);
        self::assertCount(1, $list->items);
        self::assertSame('A', $list->items[0]->name);
    }

    private function createUseCase(): CreateTemplateUseCase
    {
        return new CreateTemplateUseCase($this->templates, $this->tx, $this->auditFactory, $this->clock);
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
