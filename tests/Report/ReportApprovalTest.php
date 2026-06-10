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
use NeneField\Report\ApproveReportInput;
use NeneField\Report\ApproveReportUseCase;
use NeneField\Report\PdoReportRepository;
use NeneField\Report\RejectReportInput;
use NeneField\Report\RejectReportUseCase;
use NeneField\Report\Report;
use NeneField\Report\ReportNotInSubmittedStateException;
use NeneField\Report\ReportStatus;
use NeneField\Tests\Support\FixedClock;
use PHPUnit\Framework\TestCase;

final class ReportApprovalTest extends TestCase
{
    private const ORG = 'org-1';

    private string $dbPath;
    private PdoConnectionFactory $factory;
    private PdoDatabaseQueryExecutor $setup;
    private PdoReportRepository $reports;
    private FixedClock $clock;
    /** @var Closure(DatabaseQueryExecutorInterface): AuditRecorderInterface */
    private Closure $auditFactory;
    private PdoDatabaseTransactionManager $tx;

    protected function setUp(): void
    {
        parent::setUp();
        $tmp = tempnam(sys_get_temp_dir(), 'nf_appr_');
        self::assertIsString($tmp);
        $this->dbPath = $tmp;

        $this->factory = new PdoConnectionFactory(DatabaseConfig::sqlite($this->dbPath, 'test'));
        $this->setup = new PdoDatabaseQueryExecutor($this->factory);
        $this->setup->execute(ReportSchema::CREATE_TABLE);
        $this->setup->execute(ReportSchema::CREATE_AUDIT_TABLE);

        $this->reports = new PdoReportRepository(new PdoDatabaseQueryExecutor($this->factory));
        $this->clock = new FixedClock(new DateTimeImmutable('2026-06-11T09:00:00Z'));
        $clock = $this->clock;
        $this->auditFactory = static fn (DatabaseQueryExecutorInterface $e): AuditRecorderInterface => new AuditRecorder($e, $clock);
        $this->tx = new PdoDatabaseTransactionManager($this->factory);
    }

    protected function tearDown(): void
    {
        @unlink($this->dbPath);
        parent::tearDown();
    }

    public function test_approve_transitions_and_audits(): void
    {
        $this->insertSubmitted('rep-1', 'submitter-1');

        $approved = (new ApproveReportUseCase($this->reports, $this->tx, $this->auditFactory, $this->clock))
            ->execute(new ApproveReportInput(self::ORG, 'approver-1', 'rep-1', 'Looks good'));

        self::assertSame(ReportStatus::Approved, $approved->status);
        self::assertSame('approver-1', $approved->approverId);
        self::assertNotNull($approved->approvedAt);
        self::assertSame(1, $this->auditCount('report.approved'));
    }

    public function test_reject_transitions_with_comment_and_audits(): void
    {
        $this->insertSubmitted('rep-2', 'submitter-1');

        $rejected = (new RejectReportUseCase($this->reports, $this->tx, $this->auditFactory, $this->clock))
            ->execute(new RejectReportInput(self::ORG, 'approver-1', 'rep-2', '写真を追加してください'));

        self::assertSame(ReportStatus::Rejected, $rejected->status);
        self::assertSame('写真を追加してください', $rejected->approverComment);
        self::assertNotNull($rejected->rejectedAt);
        self::assertSame(1, $this->auditCount('report.rejected'));
    }

    public function test_cannot_approve_a_draft(): void
    {
        $this->insertDraft('rep-3', 'submitter-1');

        $this->expectException(ReportNotInSubmittedStateException::class);
        (new ApproveReportUseCase($this->reports, $this->tx, $this->auditFactory, $this->clock))
            ->execute(new ApproveReportInput(self::ORG, 'approver-1', 'rep-3', null));
    }

    private function insertSubmitted(string $id, string $userId): void
    {
        $report = $this->draft($id, $userId)->withSubmitted('2026-06-11 08:00:00');
        $this->reports->insert($this->setup, $report);
    }

    private function insertDraft(string $id, string $userId): void
    {
        $this->reports->insert($this->setup, $this->draft($id, $userId));
    }

    private function draft(string $id, string $userId): Report
    {
        return new Report(
            reportId: $id,
            organizationId: self::ORG,
            userId: $userId,
            title: 'T',
            body: 'B',
            workDate: '2026-06-11',
            status: ReportStatus::Draft,
            createdAt: '2026-06-11 00:00:00',
            updatedAt: '2026-06-11 00:00:00',
        );
    }

    private function auditCount(string $eventName): int
    {
        $read = new PdoDatabaseQueryExecutor($this->factory);
        $row = $read->fetchOne('SELECT COUNT(*) AS c FROM audit_events WHERE event_name = ?', [$eventName]);

        return $row !== null ? (int) $row['c'] : -1;
    }
}
