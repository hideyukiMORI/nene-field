<?php

declare(strict_types=1);

namespace NeneField\AuditEvent;

/**
 * Tenant-scoped read access to the append-only audit trail (multi-tenancy.md §4).
 * Writes are performed by {@see AuditRecorder} inside each mutation's transaction;
 * this interface is read-only.
 */
interface AuditEventRepositoryInterface
{
    /**
     * @return list<AuditEvent>
     */
    public function search(string $organizationId, AuditEventFilter $filter): array;

    public function count(string $organizationId, AuditEventFilter $filter): int;

    /**
     * Returns every event matching the export criteria (occurred-at bounded), up
     * to a repository safety cap. No pagination.
     *
     * @return list<AuditEvent>
     */
    public function exportRows(string $organizationId, AuditEventExportFilter $filter): array;
}
