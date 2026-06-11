<?php

declare(strict_types=1);

namespace NeneField\Attachment;

use Nene2\Database\DatabaseQueryExecutorInterface;

/**
 * Tenant-scoped data access for report attachments (multi-tenancy.md §4). Every
 * lookup is scoped by `organization_id` and `report_id`. Mutations take the
 * transaction-bound executor so the write and its audit row commit in the same
 * transaction (ADR 0014).
 */
interface AttachmentRepositoryInterface
{
    public function findById(string $organizationId, string $reportId, string $attachmentId): ?ReportAttachment;

    /**
     * @return list<ReportAttachment>
     */
    public function listByReport(string $organizationId, string $reportId): array;

    public function countByReport(string $organizationId, string $reportId): int;

    public function insert(DatabaseQueryExecutorInterface $executor, ReportAttachment $attachment): void;

    public function delete(DatabaseQueryExecutorInterface $executor, string $organizationId, string $reportId, string $attachmentId): void;
}
