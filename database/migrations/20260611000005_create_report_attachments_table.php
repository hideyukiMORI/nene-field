<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * File attachments for reports (terms.md §1: `ReportAttachment`). Tenant-scoped:
 * `organization_id` NOT NULL + index. The binary lives on the filesystem under a
 * `storage_key`; only metadata is stored here. `sha256` gives file-integrity
 * evidence (legal-compliance NF11); the `storage_key` is never exposed in any
 * API response (NF7).
 */
final class CreateReportAttachmentsTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('report_attachments', ['id' => false, 'primary_key' => 'attachment_id'])
            ->addColumn('attachment_id', 'char', ['limit' => 36, 'null' => false])
            ->addColumn('report_id', 'char', ['limit' => 36, 'null' => false])
            ->addColumn('organization_id', 'char', ['limit' => 36, 'null' => false])
            ->addColumn('uploaded_by', 'char', ['limit' => 36, 'null' => true, 'default' => null])
            ->addColumn('filename', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('mime_type', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('file_size', 'integer', ['null' => false])
            ->addColumn('sha256', 'char', ['limit' => 64, 'null' => false])
            ->addColumn('storage_key', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('created_at', 'datetime', ['null' => false])
            ->addIndex(['report_id'], ['name' => 'idx_report_attachments_report_id'])
            ->addIndex(['organization_id'], ['name' => 'idx_report_attachments_organization_id'])
            ->create();
    }
}
