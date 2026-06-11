<?php

declare(strict_types=1);

namespace NeneField\Tests\Attachment;

/**
 * SQLite DDL for the tables exercised by attachment tests: `reports` (the parent
 * aggregate), `report_attachments`, and `audit_events`. Mirrors the Phinx
 * migrations (database/migrations/...).
 */
final class AttachmentSchema
{
    public const CREATE_REPORTS_TABLE = 'CREATE TABLE reports (
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

    public const CREATE_ATTACHMENTS_TABLE = 'CREATE TABLE report_attachments (
        attachment_id CHAR(36) NOT NULL PRIMARY KEY,
        report_id CHAR(36) NOT NULL,
        organization_id CHAR(36) NOT NULL,
        uploaded_by CHAR(36) NULL,
        filename VARCHAR(255) NOT NULL,
        mime_type VARCHAR(100) NOT NULL,
        file_size INTEGER NOT NULL,
        sha256 CHAR(64) NOT NULL,
        storage_key VARCHAR(255) NOT NULL,
        created_at DATETIME NOT NULL
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
