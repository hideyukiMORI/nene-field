<?php

declare(strict_types=1);

namespace NeneField\Tests\Report;

use Nene2\Config\DatabaseConfig;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use NeneField\Report\PdoReportRepository;
use NeneField\Report\Report;
use NeneField\Report\ReportStatus;
use PHPUnit\Framework\TestCase;

final class PdoReportRepositoryTest extends TestCase
{
    private PdoDatabaseQueryExecutor $executor;
    private PdoReportRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $factory = new PdoConnectionFactory(DatabaseConfig::sqlite(':memory:', 'test'));
        $this->executor = new PdoDatabaseQueryExecutor($factory);
        $this->executor->execute(ReportSchema::CREATE_TABLE);
        $this->repository = new PdoReportRepository($this->executor);
    }

    public function test_insert_and_find_round_trips_fields_and_tags(): void
    {
        $this->repository->insert($this->executor, $this->report('rep-1', 'org-1', 'user-1', ['現場A', 'urgent']));

        $found = $this->repository->findById('org-1', 'rep-1');
        self::assertNotNull($found);
        self::assertSame('現場A 報告', $found->title);
        self::assertSame(ReportStatus::Draft, $found->status);
        self::assertSame(['現場A', 'urgent'], $found->tags);
        self::assertSame('2026-06-11', $found->workDate);
    }

    public function test_find_is_tenant_scoped(): void
    {
        $this->repository->insert($this->executor, $this->report('rep-1', 'org-1', 'user-1', []));

        self::assertNull($this->repository->findById('org-2', 'rep-1'));
    }

    public function test_delete_removes_only_the_scoped_row(): void
    {
        $this->repository->insert($this->executor, $this->report('rep-1', 'org-1', 'user-1', []));
        $this->repository->delete($this->executor, 'org-1', 'rep-1');

        self::assertNull($this->repository->findById('org-1', 'rep-1'));
    }

    /**
     * @param list<string> $tags
     */
    private function report(string $id, string $org, string $user, array $tags): Report
    {
        return new Report(
            reportId: $id,
            organizationId: $org,
            userId: $user,
            title: '現場A 報告',
            body: '本日の作業内容',
            workDate: '2026-06-11',
            status: ReportStatus::Draft,
            tags: $tags,
            createdAt: '2026-06-11 00:00:00',
            updatedAt: '2026-06-11 00:00:00',
        );
    }
}
