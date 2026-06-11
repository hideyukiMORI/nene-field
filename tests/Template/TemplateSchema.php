<?php

declare(strict_types=1);

namespace NeneField\Tests\Template;

/**
 * SQLite DDL for the `report_templates` + `audit_events` tables, shared by
 * template tests. Mirrors the Phinx migrations (database/migrations/...).
 */
final class TemplateSchema
{
    public const CREATE_TEMPLATES_TABLE = 'CREATE TABLE report_templates (
        template_id CHAR(36) NOT NULL PRIMARY KEY,
        organization_id CHAR(36) NOT NULL,
        name VARCHAR(100) NOT NULL,
        description TEXT NULL,
        fields TEXT NOT NULL DEFAULT \'[]\',
        is_default INTEGER NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL
    )';

    public const CREATE_AUDIT_TABLE = 'CREATE TABLE audit_events (
        event_id CHAR(36) NOT NULL PRIMARY KEY,
        organization_id CHAR(36) NOT NULL,
        actor_id CHAR(36) NULL,
        event_name VARCHAR(64) NOT NULL,
        entity_type VARCHAR(64) NOT NULL,
        entity_id CHAR(36) NOT NULL,
        before_json TEXT NULL,
        after_json TEXT NULL,
        request_id VARCHAR(64) NULL,
        occurred_at DATETIME NOT NULL
    )';
}
