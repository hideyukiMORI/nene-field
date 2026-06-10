<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Immutable audit trail (ADR 0014 / audit-logging.md). Append-only: no UPDATE /
 * hard DELETE. `before_json` / `after_json` hold sanitized snapshots (`before`
 * is a SQL reserved word; the API exposes them as `before` / `after`).
 */
final class CreateAuditEventsTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('audit_events', ['id' => false, 'primary_key' => 'event_id'])
            ->addColumn('event_id', 'char', ['limit' => 36, 'null' => false])
            ->addColumn('organization_id', 'char', ['limit' => 36, 'null' => false])
            ->addColumn('actor_id', 'char', ['limit' => 36, 'null' => true, 'default' => null])
            ->addColumn('event_name', 'string', ['limit' => 64, 'null' => false])
            ->addColumn('entity_type', 'string', ['limit' => 64, 'null' => false])
            ->addColumn('entity_id', 'char', ['limit' => 36, 'null' => false])
            ->addColumn('before_json', 'text', ['null' => true, 'default' => null])
            ->addColumn('after_json', 'text', ['null' => true, 'default' => null])
            ->addColumn('request_id', 'string', ['limit' => 64, 'null' => true, 'default' => null])
            ->addColumn('occurred_at', 'datetime', ['null' => false])
            ->addIndex(['organization_id'], ['name' => 'idx_audit_events_organization_id'])
            ->addIndex(['entity_type', 'entity_id'], ['name' => 'idx_audit_events_entity'])
            ->create();
    }
}
