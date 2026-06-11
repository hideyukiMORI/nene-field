<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Reusable report templates (domain-model.md / terms.md §1: `ReportTemplate`).
 * Tenant-scoped: `organization_id` NOT NULL + index. UUID string PK; `fields`
 * holds the JSON-encoded `TemplateFieldDefinition[]`. At most one template per
 * organization is the default (enforced in the use case, not by a DB constraint).
 */
final class CreateReportTemplatesTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('report_templates', ['id' => false, 'primary_key' => 'template_id'])
            ->addColumn('template_id', 'char', ['limit' => 36, 'null' => false])
            ->addColumn('organization_id', 'char', ['limit' => 36, 'null' => false])
            ->addColumn('name', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('description', 'text', ['null' => true, 'default' => null])
            ->addColumn('fields', 'text', ['null' => false, 'default' => '[]'])
            ->addColumn('is_default', 'boolean', ['null' => false, 'default' => false])
            ->addColumn('created_at', 'datetime', ['null' => false])
            ->addColumn('updated_at', 'datetime', ['null' => false])
            ->addIndex(['organization_id'], ['name' => 'idx_report_templates_organization_id'])
            ->addIndex(['organization_id', 'is_default'], ['name' => 'idx_report_templates_org_default'])
            ->create();
    }
}
