<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Tenant root (ADR 0013 / domain-model.md). UUID string primary key.
 */
final class CreateOrganizationsTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('organizations', ['id' => false, 'primary_key' => 'organization_id'])
            ->addColumn('organization_id', 'char', ['limit' => 36, 'null' => false])
            ->addColumn('name', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('slug', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('custom_domain', 'string', ['limit' => 255, 'null' => true, 'default' => null])
            ->addColumn('is_active', 'boolean', ['null' => false, 'default' => true])
            ->addColumn('ai_summary_enabled', 'boolean', ['null' => false, 'default' => false])
            ->addColumn('ai_api_url', 'string', ['limit' => 255, 'null' => true, 'default' => null])
            ->addColumn('ai_api_key', 'string', ['limit' => 255, 'null' => true, 'default' => null])
            ->addColumn('notification_email', 'string', ['limit' => 255, 'null' => true, 'default' => null])
            ->addColumn('webhook_url', 'string', ['limit' => 255, 'null' => true, 'default' => null])
            ->addColumn('created_at', 'datetime', ['null' => false])
            ->addColumn('updated_at', 'datetime', ['null' => false])
            ->addIndex(['slug'], ['unique' => true, 'name' => 'uniq_organizations_slug'])
            ->addIndex(['custom_domain'], ['unique' => true, 'name' => 'uniq_organizations_custom_domain'])
            ->create();
    }
}
