<?php

declare(strict_types=1);

namespace NeneField\Tests\Report;

/**
 * SQLite DDL for the `reports` table, shared by report tests. Mirrors the Phinx
 * migration (database/migrations/..._create_reports_table.php).
 */
final class ReportSchema
{
    public const CREATE_TABLE = 'CREATE TABLE reports (
        report_id CHAR(36) NOT NULL PRIMARY KEY,
        organization_id CHAR(36) NOT NULL,
        user_id CHAR(36) NOT NULL,
        template_id CHAR(36) NULL,
        title VARCHAR(200) NOT NULL,
        body TEXT NOT NULL,
        work_date DATE NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT \'draft\',
        tags TEXT NOT NULL DEFAULT \'[]\',
        project_code VARCHAR(100) NULL,
        invoice_work_order_id VARCHAR(255) NULL,
        records_entity_id VARCHAR(255) NULL,
        ai_summary TEXT NULL,
        ai_tags TEXT NULL,
        submitted_at DATETIME NULL,
        approved_at DATETIME NULL,
        rejected_at DATETIME NULL,
        approver_id CHAR(36) NULL,
        approver_comment TEXT NULL,
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
