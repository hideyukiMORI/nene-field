<?php

declare(strict_types=1);

namespace NeneField\Tests\Report;

use Closure;
use DateTimeImmutable;
use Nene2\Config\DatabaseConfig;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use Nene2\Database\PdoDatabaseTransactionManager;
use NeneField\AuditEvent\AuditRecorder;
use NeneField\AuditEvent\AuditRecorderInterface;
use NeneField\Auth\Role;
use NeneField\Report\CreateReportInput;
use NeneField\Report\CreateReportUseCase;
use NeneField\Report\DeleteReportUseCase;
use NeneField\Report\GetReportUseCase;
use NeneField\Report\PdoReportRepository;
use NeneField\Report\ReportNotEditableException;
use NeneField\Report\ReportNotFoundException;
use NeneField\Report\ReportStatus;
use NeneField\Report\SubmitReportUseCase;
use NeneField\Report\UpdateReportInput;
use NeneField\Report\UpdateReportUseCase;
use NeneField\Tests\Support\FixedClock;
use PHPUnit\Framework\TestCase;

/**
 * End-to-end report lifecycle against file SQLite, exercising the real
 * transaction manager + audit recorder: every mutation persists and writes its
 * `report.*` audit event in the same transaction (ADR 0014).
 */
final class ReportLifecycleTest extends TestCase
{
    private const ORG = 'org-1';
    private const OWNER = 'user-1';

    private string $dbPath;
    private PdoConnectionFactory $factory;
    private PdoReportRepository $reports;
    private FixedClock $clock;
    /** @var Closure(DatabaseQueryExecutorInterface): AuditRecorderInterface */
    private Closure $auditFactory;
    private PdoDatabaseTransactionManager $tx;

    protected function setUp(): void
    {
        parent::setUp();

        $tmp = tempnam(sys_get_temp_dir(), 'nf_report_');
        self::assertIsString($tmp);
        $this->dbPath = $tmp;

        $this->factory = new PdoConnectionFactory(DatabaseConfig::sqlite($this->dbPath, 'test'));
        $setup = new PdoDatabaseQueryExecutor($this->factory);
        $setup->execute(ReportSchema::CREATE_TABLE);
        $setup->execute(ReportSchema::CREATE_AUDIT_TABLE);

        $this->reports = new PdoReportRepository(new PdoDatabaseQueryExecutor($this->factory));
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
        // create
        $created = $this->createUseCase()->execute(new CreateReportInput(
            self::ORG,
            self::OWNER,
            '現場A 報告',
            '作業内容',
            '2026-06-11',
            ['urgent'],
        ));
        self::assertSame(ReportStatus::Draft, $created->status);
        $persisted = $this->reports->findById(self::ORG, $created->reportId);
        self::assertNotNull($persisted);
        self::assertSame(1, $this->auditCount('report.created', $created->reportId));

        // update
        $updated = (new UpdateReportUseCase($this->reports, $this->tx, $this->auditFactory, $this->clock))->execute(
            new UpdateReportInput(self::ORG, self::OWNER, $created->reportId, '現場A 報告(改)', '修正後', '2026-06-12', ['done']),
        );
        self::assertSame('現場A 報告(改)', $updated->title);
        self::assertSame(1, $this->auditCount('report.edited', $created->reportId));

        // submit
        $submitted = (new SubmitReportUseCase($this->reports, $this->tx, $this->auditFactory, $this->clock))
            ->execute(self::ORG, self::OWNER, $created->reportId);
        self::assertSame(ReportStatus::Submitted, $submitted->status);
        self::assertNotNull($submitted->submittedAt);
        self::assertSame(1, $this->auditCount('report.submitted', $created->reportId));
    }

    public function test_non_owner_cannot_update(): void
    {
        $created = $this->createUseCase()->execute(new CreateReportInput(self::ORG, self::OWNER, 'T', 'B', '2026-06-11'));

        $this->expectException(ReportNotFoundException::class);
        (new UpdateReportUseCase($this->reports, $this->tx, $this->auditFactory, $this->clock))->execute(
            new UpdateReportInput(self::ORG, 'intruder', $created->reportId, 'X', 'Y', '2026-06-11'),
        );
    }

    public function test_submitted_report_is_not_editable(): void
    {
        $created = $this->createUseCase()->execute(new CreateReportInput(self::ORG, self::OWNER, 'T', 'B', '2026-06-11'));
        (new SubmitReportUseCase($this->reports, $this->tx, $this->auditFactory, $this->clock))
            ->execute(self::ORG, self::OWNER, $created->reportId);

        $this->expectException(ReportNotEditableException::class);
        (new UpdateReportUseCase($this->reports, $this->tx, $this->auditFactory, $this->clock))->execute(
            new UpdateReportInput(self::ORG, self::OWNER, $created->reportId, 'X', 'Y', '2026-06-11'),
        );
    }

    public function test_delete_removes_draft_and_audits(): void
    {
        $created = $this->createUseCase()->execute(new CreateReportInput(self::ORG, self::OWNER, 'T', 'B', '2026-06-11'));

        (new DeleteReportUseCase($this->reports, $this->tx, $this->auditFactory))
            ->execute(self::ORG, self::OWNER, $created->reportId);

        self::assertNull($this->reports->findById(self::ORG, $created->reportId));
        self::assertSame(1, $this->auditCount('report.deleted', $created->reportId));
    }

    public function test_get_visibility_owner_other_and_approver(): void
    {
        $created = $this->createUseCase()->execute(new CreateReportInput(self::ORG, self::OWNER, 'T', 'B', '2026-06-11'));
        $get = new GetReportUseCase($this->reports);

        self::assertNotNull($get->execute(self::ORG, $created->reportId, self::OWNER, Role::Submitter));
        self::assertNull($get->execute(self::ORG, $created->reportId, 'other-submitter', Role::Submitter));
        self::assertNotNull($get->execute(self::ORG, $created->reportId, 'mgr', Role::Approver));
    }

    private function createUseCase(): CreateReportUseCase
    {
        return new CreateReportUseCase($this->reports, $this->tx, $this->auditFactory, $this->clock);
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
