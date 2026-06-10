<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Operator accounts. Tenant-scoped: every user belongs to one organization,
 * and email uniqueness is per tenant (multi-tenancy.md §6). UUID string PK.
 */
final class CreateUsersTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('users', ['id' => false, 'primary_key' => 'user_id'])
            ->addColumn('user_id', 'char', ['limit' => 36, 'null' => false])
            ->addColumn('organization_id', 'char', ['limit' => 36, 'null' => false])
            ->addColumn('name', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('email', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('password_hash', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('role', 'string', ['limit' => 20, 'null' => false])
            ->addColumn('is_active', 'boolean', ['null' => false, 'default' => true])
            ->addColumn('created_at', 'datetime', ['null' => false])
            ->addColumn('updated_at', 'datetime', ['null' => false])
            ->addIndex(['organization_id', 'email'], ['unique' => true, 'name' => 'uniq_users_email_org'])
            ->addIndex(['organization_id'], ['name' => 'idx_users_organization_id'])
            ->create();
    }
}
