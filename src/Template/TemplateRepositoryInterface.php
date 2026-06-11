<?php

declare(strict_types=1);

namespace NeneField\Template;

use Nene2\Database\DatabaseQueryExecutorInterface;

/**
 * Tenant-scoped data access for report templates. Every lookup is scoped by
 * `organization_id` (multi-tenancy.md §4). Mutations take the transaction-bound
 * executor so the write and its audit row commit in the same transaction
 * (ADR 0014).
 */
interface TemplateRepositoryInterface
{
    public function findById(string $organizationId, string $templateId): ?ReportTemplate;

    /**
     * @return list<ReportTemplate>
     */
    public function listByOrg(string $organizationId): array;

    public function insert(DatabaseQueryExecutorInterface $executor, ReportTemplate $template): void;

    public function update(DatabaseQueryExecutorInterface $executor, ReportTemplate $template): void;

    public function delete(DatabaseQueryExecutorInterface $executor, string $organizationId, string $templateId): void;

    /**
     * Clears the default flag on every template in the org except `$exceptId`,
     * so at most one template stays the default.
     */
    public function clearDefault(DatabaseQueryExecutorInterface $executor, string $organizationId, ?string $exceptId, string $now): void;
}
