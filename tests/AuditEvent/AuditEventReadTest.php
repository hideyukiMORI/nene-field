<?php

declare(strict_types=1);

namespace NeneField\Tests\AuditEvent;

use DateTimeImmutable;
use Nene2\Config\DatabaseConfig;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use Nene2\Database\PdoDatabaseTransactionManager;
use NeneField\AuditEvent\AuditEventCsvFormatter;
use NeneField\AuditEvent\AuditEventExportFilter;
use NeneField\AuditEvent\AuditEventFilter;
use NeneField\AuditEvent\AuditRecorder;
use NeneField\AuditEvent\AuditRecorderInterface;
use NeneField\AuditEvent\ExportAuditEventsUseCase;
use NeneField\AuditEvent\ListAuditEventsUseCase;
use NeneField\AuditEvent\PdoAuditEventRepository;
use NeneField\Tests\Report\ReportSchema;
use NeneField\Tests\Support\FixedClock;
use PHPUnit\Framework\TestCase;

/**
 * End-to-end audit read + export against file SQLite: filtering, pagination,
 * tenant isolation, snapshot decoding, actor-name join, and the `audit.exported`
 * self-audit (filters + count only).
 */
final class AuditEventReadTest extends TestCase
{
    private const ORG = 'org-1';
    private const BOM = "\xEF\xBB\xBF";

    private string $dbPath;
    private PdoConnectionFactory $factory;
    private PdoAuditEventRepository $events;

    protected function setUp(): void
    {
        parent::setUp();

        $tmp = tempnam(sys_get_temp_dir(), 'nf_auditread_');
        self::assertIsString($tmp);
        $this->dbPath = $tmp;

        $this->factory = new PdoConnectionFactory(DatabaseConfig::sqlite($this->dbPath, 'test'));
        $setup = new PdoDatabaseQueryExecutor($this->factory);
        $setup->execute(ReportSchema::CREATE_AUDIT_TABLE);
        $setup->execute(ReportSchema::CREATE_USERS_TABLE);

        $this->events = new PdoAuditEventRepository(new PdoDatabaseQueryExecutor($this->factory));

        $this->seedUser(self::ORG, 'admin-1', '管理者');
        $this->seedEvent('e1', self::ORG, 'admin-1', 'report.created', 'Report', 'r1', null, ['title' => 'A'], '2026-06-10 09:00:00');
        $this->seedEvent('e2', self::ORG, 'admin-1', 'report.approved', 'Report', 'r1', ['status' => 'submitted'], ['status' => 'approved'], '2026-06-10 10:00:00');
        $this->seedEvent('e3', self::ORG, null, 'user.created', 'User', 'u9', null, ['name' => 'X'], '2026-06-11 08:00:00');
        $this->seedEvent('eX', 'org-2', 'admin-2', 'report.created', 'Report', 'r2', null, ['title' => 'Z'], '2026-06-10 09:00:00');
    }

    protected function tearDown(): void
    {
        @unlink($this->dbPath);
        parent::tearDown();
    }

    public function test_list_returns_all_org_events_newest_first_with_decoded_snapshots(): void
    {
        $output = (new ListAuditEventsUseCase($this->events))->execute(self::ORG, new AuditEventFilter());

        self::assertSame(3, $output->total);
        self::assertSame('e3', $output->items[0]->eventId, 'newest first');
        // actor join + snapshot decode
        $approved = $output->items[1];
        self::assertSame('e2', $approved->eventId);
        self::assertSame('管理者', $approved->actorName);
        self::assertSame(['status' => 'submitted'], $approved->before);
        self::assertSame(['status' => 'approved'], $approved->after);
        // system actor has null name
        self::assertNull($output->items[0]->actorName);
    }

    public function test_list_filters_by_entity_type_and_event_name(): void
    {
        $byType = (new ListAuditEventsUseCase($this->events))->execute(self::ORG, new AuditEventFilter(entityType: 'User'));
        self::assertSame(1, $byType->total);
        self::assertSame('user.created', $byType->items[0]->eventName);

        $byName = (new ListAuditEventsUseCase($this->events))->execute(self::ORG, new AuditEventFilter(eventName: 'report.approved'));
        self::assertSame(1, $byName->total);
        self::assertSame('e2', $byName->items[0]->eventId);
    }

    public function test_list_filters_by_actor_and_occurred_range(): void
    {
        $byActor = (new ListAuditEventsUseCase($this->events))->execute(self::ORG, new AuditEventFilter(actorId: 'admin-1'));
        self::assertSame(2, $byActor->total);

        $byRange = (new ListAuditEventsUseCase($this->events))->execute(self::ORG, new AuditEventFilter(occurredFrom: '2026-06-11 00:00:00'));
        self::assertSame(1, $byRange->total);
        self::assertSame('e3', $byRange->items[0]->eventId);
    }

    public function test_list_pagination(): void
    {
        $page = (new ListAuditEventsUseCase($this->events))->execute(self::ORG, new AuditEventFilter(limit: 2, offset: 0));
        self::assertSame(3, $page->total);
        self::assertCount(2, $page->items);

        $next = (new ListAuditEventsUseCase($this->events))->execute(self::ORG, new AuditEventFilter(limit: 2, offset: 2));
        self::assertCount(1, $next->items);
    }

    public function test_list_is_tenant_scoped(): void
    {
        $other = (new ListAuditEventsUseCase($this->events))->execute('org-2', new AuditEventFilter());
        self::assertSame(1, $other->total);
        self::assertSame('eX', $other->items[0]->eventId);
    }

    public function test_export_has_bom_rows_and_self_audits(): void
    {
        $export = $this->exportUseCase()->execute(self::ORG, 'admin-1', new AuditEventExportFilter('2026-06-01 00:00:00', '2026-06-30 23:59:59'));

        self::assertStringStartsWith(self::BOM, $export->csv);
        self::assertSame(3, $export->rowCount);
        self::assertStringContainsString('report.approved', $export->csv);
        self::assertStringContainsString('e3', $export->csv);

        $read = new PdoDatabaseQueryExecutor($this->factory);
        $row = $read->fetchOne("SELECT entity_type, entity_id, after_json FROM audit_events WHERE event_name = 'audit.exported'", []);
        self::assertNotNull($row);
        self::assertSame('AuditEvent', (string) $row['entity_type']);
        self::assertSame(self::ORG, (string) $row['entity_id']);

        $after = json_decode((string) $row['after_json'], true);
        self::assertIsArray($after);
        self::assertSame('2026-06-01 00:00:00', $after['occurred_from']);
        self::assertSame(3, $after['row_count']);
    }

    public function test_export_filters_by_entity_type(): void
    {
        $export = $this->exportUseCase()->execute(self::ORG, 'admin-1', new AuditEventExportFilter('2026-06-01 00:00:00', '2026-06-30 23:59:59', 'User'));
        self::assertSame(1, $export->rowCount);
        self::assertStringContainsString('user.created', $export->csv);
        self::assertStringNotContainsString('report.approved', $export->csv);
    }

    private function exportUseCase(): ExportAuditEventsUseCase
    {
        $clock = new FixedClock(new DateTimeImmutable('2026-06-12T00:00:00Z'));
        $auditFactory = static fn (DatabaseQueryExecutorInterface $exec): AuditRecorderInterface => new AuditRecorder($exec, $clock);

        return new ExportAuditEventsUseCase(
            $this->events,
            new AuditEventCsvFormatter(),
            new PdoDatabaseTransactionManager($this->factory),
            $auditFactory,
        );
    }

    private function seedUser(string $org, string $userId, string $name): void
    {
        $write = new PdoDatabaseQueryExecutor($this->factory);
        $write->execute(
            'INSERT INTO users (user_id, organization_id, name, email, password_hash, role, is_active, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?)',
            [$userId, $org, $name, $userId . '@example.com', 'x', 'admin', '2026-06-01 00:00:00', '2026-06-01 00:00:00'],
        );
    }

    /**
     * @param array<string, mixed>|null $before
     * @param array<string, mixed>|null $after
     */
    private function seedEvent(string $eventId, string $org, ?string $actorId, string $eventName, string $entityType, string $entityId, ?array $before, ?array $after, string $occurredAt): void
    {
        $write = new PdoDatabaseQueryExecutor($this->factory);
        $write->execute(
            'INSERT INTO audit_events (event_id, organization_id, actor_id, event_name, entity_type, entity_id, before_json, after_json, request_id, occurred_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $eventId, $org, $actorId, $eventName, $entityType, $entityId,
                $before !== null ? (string) json_encode($before) : null,
                $after !== null ? (string) json_encode($after) : null,
                null, $occurredAt,
            ],
        );
    }
}
