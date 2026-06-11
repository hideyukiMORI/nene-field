<?php

declare(strict_types=1);

namespace NeneField\Tests\Export;

use Closure;
use DateTimeImmutable;
use Nene2\Config\DatabaseConfig;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use Nene2\Database\PdoDatabaseTransactionManager;
use NeneField\AuditEvent\AuditRecorder;
use NeneField\AuditEvent\AuditRecorderInterface;
use NeneField\Export\ExportReportsUseCase;
use NeneField\Export\ReportCsvFormatter;
use NeneField\Report\PdoReportRepository;
use NeneField\Report\ReportExportFilter;
use NeneField\Report\ReportStatus;
use NeneField\Tests\Report\ReportSchema;
use NeneField\Tests\Support\FixedClock;
use PHPUnit\Framework\TestCase;

/**
 * End-to-end report CSV export against file SQLite: status/date filtering, tenant
 * isolation, and the audit invariant that `report.exported` records the filter
 * criteria + row count but never the exported rows (terms.md §8).
 */
final class ReportExportTest extends TestCase
{
    private const ORG = 'org-1';
    private const BOM = "\xEF\xBB\xBF";

    private string $dbPath;
    private PdoConnectionFactory $factory;
    private PdoReportRepository $reports;
    /** @var Closure(DatabaseQueryExecutorInterface): AuditRecorderInterface */
    private Closure $auditFactory;
    private PdoDatabaseTransactionManager $tx;

    protected function setUp(): void
    {
        parent::setUp();

        $tmp = tempnam(sys_get_temp_dir(), 'nf_export_');
        self::assertIsString($tmp);
        $this->dbPath = $tmp;

        $this->factory = new PdoConnectionFactory(DatabaseConfig::sqlite($this->dbPath, 'test'));
        $setup = new PdoDatabaseQueryExecutor($this->factory);
        $setup->execute(ReportSchema::CREATE_TABLE);
        $setup->execute(ReportSchema::CREATE_USERS_TABLE);
        $setup->execute(ReportSchema::CREATE_AUDIT_TABLE);

        $this->reports = new PdoReportRepository(new PdoDatabaseQueryExecutor($this->factory));
        $clock = new FixedClock(new DateTimeImmutable('2026-06-11T08:00:00Z'));
        $this->auditFactory = static fn (DatabaseQueryExecutorInterface $exec): AuditRecorderInterface
            => new AuditRecorder($exec, $clock);
        $this->tx = new PdoDatabaseTransactionManager($this->factory);

        $this->seedUser(self::ORG, 'u-1', '田中太郎');
    }

    protected function tearDown(): void
    {
        @unlink($this->dbPath);
        parent::tearDown();
    }

    public function test_exports_only_matching_status_and_date_with_bom(): void
    {
        $this->seedReport('r-approved', self::ORG, 'u-1', '2026-06-10', 'approved', '機密タイトルA');
        $this->seedReport('r-draft', self::ORG, 'u-1', '2026-06-10', 'draft', 'Draft title');
        $this->seedReport('r-old', self::ORG, 'u-1', '2026-05-01', 'approved', 'Old title');

        $export = $this->useCase()->execute(self::ORG, 'admin-1', new ReportExportFilter(
            workDateFrom: '2026-06-01',
            workDateTo: '2026-06-30',
            statuses: [ReportStatus::Approved],
        ));

        self::assertStringStartsWith(self::BOM, $export->csv);
        self::assertSame(1, $export->rowCount, 'only the in-range approved report');
        self::assertStringContainsString('r-approved', $export->csv);
        self::assertStringContainsString('田中太郎', $export->csv, 'user_name joined');
        self::assertStringNotContainsString('r-draft', $export->csv);
        self::assertStringNotContainsString('r-old', $export->csv);
    }

    public function test_audit_records_filters_and_count_but_not_rows(): void
    {
        $this->seedReport('r-1', self::ORG, 'u-1', '2026-06-10', 'approved', '機密タイトルA');

        $this->useCase()->execute(self::ORG, 'admin-1', new ReportExportFilter(
            workDateFrom: '2026-06-01',
            workDateTo: '2026-06-30',
            statuses: [ReportStatus::Approved],
            projectCode: '機密タイトルA',
        ));

        $read = new PdoDatabaseQueryExecutor($this->factory);
        $row = $read->fetchOne("SELECT event_name, entity_type, entity_id, after_json FROM audit_events WHERE event_name = 'report.exported'", []);
        self::assertNotNull($row);
        self::assertSame('Report', (string) $row['entity_type']);
        self::assertSame(self::ORG, (string) $row['entity_id']);

        $after = json_decode((string) $row['after_json'], true);
        self::assertIsArray($after);
        self::assertSame('2026-06-01', $after['work_date_from']);
        self::assertSame('2026-06-30', $after['work_date_to']);
        self::assertSame(['approved'], $after['statuses']);
        self::assertSame(1, $after['row_count']);

        // The exported row content (the report title) must NOT be in the trail.
        self::assertStringNotContainsString('r-1', (string) $row['after_json']);
    }

    public function test_export_is_scoped_to_organization(): void
    {
        $this->seedUser('org-2', 'u-2', 'Other');
        $this->seedReport('mine', self::ORG, 'u-1', '2026-06-10', 'approved', null);
        $this->seedReport('theirs', 'org-2', 'u-2', '2026-06-10', 'approved', null);

        $export = $this->useCase()->execute(self::ORG, 'admin-1', new ReportExportFilter('2026-06-01', '2026-06-30', [ReportStatus::Approved]));

        self::assertSame(1, $export->rowCount);
        self::assertStringContainsString('mine', $export->csv);
        self::assertStringNotContainsString('theirs', $export->csv);
    }

    private function useCase(): ExportReportsUseCase
    {
        return new ExportReportsUseCase($this->reports, new ReportCsvFormatter(), $this->tx, $this->auditFactory);
    }

    private function seedUser(string $org, string $userId, string $name): void
    {
        $write = new PdoDatabaseQueryExecutor($this->factory);
        $write->execute(
            'INSERT INTO users (user_id, organization_id, name, email, password_hash, role, is_active, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?)',
            [$userId, $org, $name, $userId . '@example.com', 'x', 'submitter', '2026-06-01 00:00:00', '2026-06-01 00:00:00'],
        );
    }

    private function seedReport(string $reportId, string $org, string $userId, string $workDate, string $status, ?string $projectCode): void
    {
        $write = new PdoDatabaseQueryExecutor($this->factory);
        $write->execute(
            'INSERT INTO reports (report_id, organization_id, user_id, title, body, work_date, status, tags, project_code, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$reportId, $org, $userId, 'Title ' . $reportId, 'B', $workDate, $status, '[]', $projectCode, '2026-06-11 00:00:00', '2026-06-11 00:00:00'],
        );
    }
}
