<?php

declare(strict_types=1);

namespace NeneField\Tests\Report;

use Nene2\Config\DatabaseConfig;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use NeneField\Auth\Role;
use NeneField\Report\ListReportsUseCase;
use NeneField\Report\PdoReportRepository;
use NeneField\Report\Report;
use NeneField\Report\ReportFilter;
use NeneField\Report\ReportStatus;
use PHPUnit\Framework\TestCase;

final class ReportListTest extends TestCase
{
    private const ORG = 'org-1';

    private PdoDatabaseQueryExecutor $executor;
    private PdoReportRepository $reports;
    private ListReportsUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        $factory = new PdoConnectionFactory(DatabaseConfig::sqlite(':memory:', 'test'));
        $this->executor = new PdoDatabaseQueryExecutor($factory);
        $this->executor->execute(ReportSchema::CREATE_TABLE);
        $this->executor->execute(ReportSchema::CREATE_USERS_TABLE);

        $this->user('u1', '田中');
        $this->user('u2', '佐藤');
        $this->reports = new PdoReportRepository($this->executor);

        $this->reports->insert($this->executor, $this->report('rep-a', 'u1', ReportStatus::Draft, '2026-06-10'));
        $this->reports->insert($this->executor, $this->report('rep-b', 'u1', ReportStatus::Submitted, '2026-06-11'));
        $this->reports->insert($this->executor, $this->report('rep-c', 'u2', ReportStatus::Approved, '2026-06-12'));

        $this->useCase = new ListReportsUseCase($this->reports);
    }

    public function test_approver_sees_all_with_user_name_joined_and_total(): void
    {
        $out = $this->useCase->execute(self::ORG, 'mgr', Role::Admin, new ReportFilter());

        self::assertSame(3, $out->total);
        self::assertCount(3, $out->items);
        // Default sort work_date_desc → newest (rep-c, 佐藤) first.
        self::assertSame('rep-c', $out->items[0]->reportId);
        self::assertSame('佐藤', $out->items[0]->userName);
    }

    public function test_submitter_is_scoped_to_own_reports(): void
    {
        $out = $this->useCase->execute(self::ORG, 'u1', Role::Submitter, new ReportFilter());

        self::assertSame(2, $out->total);
        foreach ($out->items as $item) {
            self::assertSame('u1', $item->userId);
        }
    }

    public function test_status_filter(): void
    {
        $out = $this->useCase->execute(self::ORG, 'mgr', Role::Admin, new ReportFilter(statuses: [ReportStatus::Approved]));

        self::assertSame(1, $out->total);
        self::assertSame('rep-c', $out->items[0]->reportId);
    }

    public function test_pagination_limits_items_but_reports_full_total(): void
    {
        $out = $this->useCase->execute(self::ORG, 'mgr', Role::Admin, new ReportFilter(limit: 1, offset: 0));

        self::assertCount(1, $out->items);
        self::assertSame(3, $out->total);
        self::assertSame(1, $out->limit);
    }

    private function user(string $id, string $name): void
    {
        $this->executor->execute(
            'INSERT INTO users (user_id, organization_id, name, email, password_hash, role, is_active, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$id, self::ORG, $name, $id . '@example.com', 'h', 'submitter', 1, '2026-06-01 00:00:00', '2026-06-01 00:00:00'],
        );
    }

    private function report(string $id, string $userId, ReportStatus $status, string $workDate): Report
    {
        return new Report(
            reportId: $id,
            organizationId: self::ORG,
            userId: $userId,
            title: 'T ' . $id,
            body: 'B',
            workDate: $workDate,
            status: $status,
            createdAt: $workDate . ' 00:00:00',
            updatedAt: $workDate . ' 00:00:00',
        );
    }
}
