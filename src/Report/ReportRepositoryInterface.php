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

    public function insert(DatabaseQueryExecutorInterface $executor, Report $report): void;

    public function update(DatabaseQueryExecutorInterface $executor, Report $report): void;

    public function delete(DatabaseQueryExecutorInterface $executor, string $organizationId, string $reportId): void;
}
