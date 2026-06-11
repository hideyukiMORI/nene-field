<?php

declare(strict_types=1);

namespace NeneField\Report;

use Nene2\Database\DatabaseQueryExecutorInterface;

/**
 * Tenant-scoped data access for reports (multi-tenancy.md §4). Reads use the
 * injected executor; writes take the transaction-bound executor so they run in
 * the same transaction as the audit write (ADR 0014).
 */
interface ReportRepositoryInterface
{
    public function findById(string $organizationId, string $reportId): ?Report;

    /**
     * @return list<ReportSummary>
     */
    public function search(string $organizationId, ReportFilter $filter): array;

    public function count(string $organizationId, ReportFilter $filter): int;

    /**
     * Returns every report matching the export criteria (work-date bounded), up
     * to a repository safety cap. No pagination.
     *
     * @return list<ReportExportRow>
     */
    public function exportRows(string $organizationId, ReportExportFilter $filter): array;

    public function insert(DatabaseQueryExecutorInterface $executor, Report $report): void;

    public function update(DatabaseQueryExecutorInterface $executor, Report $report): void;

    public function delete(DatabaseQueryExecutorInterface $executor, string $organizationId, string $reportId): void;
}
