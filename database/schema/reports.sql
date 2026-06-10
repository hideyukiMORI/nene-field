-- Schema snapshot: reports (domain-model.md). Reference only; Phinx is the source
-- of truth. Tenant-scoped (organization_id NOT NULL + index). UUID string PK.
CREATE TABLE reports (
    report_id             CHAR(36)     NOT NULL,
    organization_id       CHAR(36)     NOT NULL,
    user_id               CHAR(36)     NOT NULL, -- submitter
    template_id           CHAR(36)         NULL DEFAULT NULL,
    title                 VARCHAR(200) NOT NULL,
    body                  TEXT         NOT NULL,
    work_date             DATE         NOT NULL,
    status                VARCHAR(20)  NOT NULL DEFAULT 'draft', -- draft|submitted|approved|rejected
    tags                  TEXT         NOT NULL DEFAULT '[]',    -- JSON array
    project_code          VARCHAR(100)     NULL DEFAULT NULL,
    invoice_work_order_id VARCHAR(255)     NULL DEFAULT NULL,    -- read-only HTTP ref (nene-invoice)
    records_entity_id     VARCHAR(255)     NULL DEFAULT NULL,    -- read-only HTTP ref (nene-records)
    ai_summary            TEXT             NULL DEFAULT NULL,
    ai_tags               TEXT             NULL DEFAULT NULL,    -- JSON array
    submitted_at          DATETIME         NULL DEFAULT NULL,
    approved_at           DATETIME         NULL DEFAULT NULL,
    rejected_at           DATETIME         NULL DEFAULT NULL,
    approver_id           CHAR(36)         NULL DEFAULT NULL,
    approver_comment      TEXT             NULL DEFAULT NULL,
    created_at            DATETIME     NOT NULL,
    updated_at            DATETIME     NOT NULL,
    PRIMARY KEY (report_id)
);
CREATE INDEX idx_reports_organization_id ON reports (organization_id);
CREATE INDEX idx_reports_org_user ON reports (organization_id, user_id);
CREATE INDEX idx_reports_org_work_date ON reports (organization_id, work_date);
