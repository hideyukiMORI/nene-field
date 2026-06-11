<?php

declare(strict_types=1);

namespace NeneField\Tests\Organization;

/**
 * SQLite DDL for the `organizations` + `audit_events` tables, shared by org
 * management tests. Mirrors the Phinx migrations (database/migrations/...).
 */
final class OrganizationSchema
{
    public const CREATE_ORGANIZATIONS_TABLE = 'CREATE TABLE organizations (
        organization_id CHAR(36) NOT NULL PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        slug VARCHAR(100) NOT NULL,
        custom_domain VARCHAR(255) NULL,
        is_active INTEGER NOT NULL DEFAULT 1,
        ai_summary_enabled INTEGER NOT NULL DEFAULT 0,
        ai_api_url VARCHAR(255) NULL,
        ai_api_key VARCHAR(255) NULL,
        notification_email VARCHAR(255) NULL,
        webhook_url VARCHAR(255) NULL,
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
