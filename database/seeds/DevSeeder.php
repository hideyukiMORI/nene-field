<?php

declare(strict_types=1);

use NeneField\Support\Uuid;
use Phinx\Seed\AbstractSeed;

/**
 * Local development seed (idempotent): one organization (`demo`), one admin
 * user, and one default report template. Run with `composer migrations:seed`.
 * DEV ONLY — never run in production; the password is well-known.
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

        $fields = [
            ['name' => 'work_summary', 'label' => '作業内容', 'type' => 'textarea', 'required' => true],
            ['name' => 'hours', 'label' => '作業時間', 'type' => 'number', 'required' => false],
            ['name' => 'weather', 'label' => '天候', 'type' => 'select', 'required' => false, 'options' => ['晴れ', '曇り', '雨']],
        ];

        $this->table('report_templates')->insert([
            'template_id' => Uuid::v4(),
            'organization_id' => $orgId,
            'name' => '日報（標準）',
            'description' => 'デモ組織の既定テンプレート',
            'fields' => json_encode($fields, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'is_default' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ])->saveData();
    }
}
