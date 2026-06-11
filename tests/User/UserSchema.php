<?php

declare(strict_types=1);

namespace NeneField\Tests\User;

/**
 * SQLite DDL for the `users` + `audit_events` tables, shared by user tests.
 * Mirrors the Phinx migrations (database/migrations/...).
 */
final class UserSchema
{
    public const CREATE_USERS_TABLE = 'CREATE TABLE users (
        user_id CHAR(36) NOT NULL PRIMARY KEY,
        organization_id CHAR(36) NOT NULL,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        role VARCHAR(20) NOT NULL,
        is_active INTEGER NOT NULL DEFAULT 1,
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
