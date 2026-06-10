<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Daily reports (domain-model.md). Tenant-scoped: `organization_id` NOT NULL +
 * index. Lifecycle status draft → submitted → approved / rejected (terms.md §2).
 * UUID string PK; `tags` / `ai_tags` stored as JSON text.
 */
final class CreateReportsTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('reports', ['id' => false, 'primary_key' => 'report_id'])
            ->addColumn('report_id', 'char', ['limit' => 36, 'null' => false])
            ->addColumn('organization_id', 'char', ['limit' => 36, 'null' => false])
            ->addColumn('user_id', 'char', ['limit' => 36, 'null' => false])
            ->addColumn('template_id', 'char', ['limit' => 36, 'null' => true, 'default' => null])
            ->addColumn('title', 'string', ['limit' => 200, 'null' => false])
            ->addColumn('body', 'text', ['null' => false])
            ->addColumn('work_date', 'date', ['null' => false])
            ->addColumn('status', 'string', ['limit' => 20, 'null' => false, 'default' => 'draft'])
            ->addColumn('tags', 'text', ['null' => false, 'default' => '[]'])
            ->addColumn('project_code', 'string', ['limit' => 100, 'null' => true, 'default' => null])
            ->addColumn('invoice_work_order_id', 'string', ['limit' => 255, 'null' => true, 'default' => null])
            ->addColumn('records_entity_id', 'string', ['limit' => 255, 'null' => true, 'default' => null])
            ->addColumn('ai_summary', 'text', ['null' => true, 'default' => null])
            ->addColumn('ai_tags', 'text', ['null' => true, 'default' => null])
            ->addColumn('submitted_at', 'datetime', ['null' => true, 'default' => null])
            ->addColumn('approved_at', 'datetime', ['null' => true, 'default' => null])
            ->addColumn('rejected_at', 'datetime', ['null' => true, 'default' => null])
            ->addColumn('approver_id', 'char', ['limit' => 36, 'null' => true, 'default' => null])
            ->addColumn('approver_comment', 'text', ['null' => true, 'default' => null])
            ->addColumn('created_at', 'datetime', ['null' => false])
            ->addColumn('updated_at', 'datetime', ['null' => false])
            ->addIndex(['organization_id'], ['name' => 'idx_reports_organization_id'])
            ->addIndex(['organization_id', 'user_id'], ['name' => 'idx_reports_org_user'])
            ->addIndex(['organization_id', 'work_date'], ['name' => 'idx_reports_org_work_date'])
            ->create();
    }
}
