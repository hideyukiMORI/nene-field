<?php

declare(strict_types=1);

use NeneField\Support\Uuid;
use Phinx\Seed\AbstractSeed;

/**
 * Local development seed (idempotent): one organization (`demo`) and one admin
 * user. Run with `composer migrations:seed`. DEV ONLY — never run in production;
 * the password is well-known.
 *
 * Login locally with NENE_FIELD_TENANT_RESOLUTION=single, NENE_FIELD_ORG_SLUG=demo:
 *   email: admin@example.com   password: password
 */
final class DevSeeder extends AbstractSeed
{
    public function run(): void
    {
        $existing = $this->fetchRow("SELECT organization_id FROM organizations WHERE slug = 'demo'");

        if (is_array($existing)) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $orgId = Uuid::v4();
        $userId = Uuid::v4();

        $this->table('organizations')->insert([
            'organization_id' => $orgId,
            'name' => 'Demo Organization',
            'slug' => 'demo',
            'custom_domain' => null,
            'is_active' => 1,
            'ai_summary_enabled' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ])->saveData();

        $this->table('users')->insert([
            'user_id' => $userId,
            'organization_id' => $orgId,
            'name' => 'Demo Admin',
            'email' => 'admin@example.com',
            'password_hash' => password_hash('password', PASSWORD_BCRYPT),
            'role' => 'admin',
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ])->saveData();
    }
}
