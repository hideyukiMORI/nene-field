-- Schema snapshot: organizations (tenant root). Reference only; Phinx migrations
-- are the source of truth. UUID string primary key (domain-model.md / ADR 0013).
CREATE TABLE organizations (
    organization_id    CHAR(36)     NOT NULL,
    name               VARCHAR(255) NOT NULL,
    slug               VARCHAR(100) NOT NULL,
    custom_domain      VARCHAR(255)     NULL DEFAULT NULL,
    is_active          BOOLEAN      NOT NULL DEFAULT 1,
    ai_summary_enabled BOOLEAN      NOT NULL DEFAULT 0,
    ai_api_url         VARCHAR(255)     NULL DEFAULT NULL,
    ai_api_key         VARCHAR(255)     NULL DEFAULT NULL, -- secret: never in API/audit responses
    notification_email VARCHAR(255)     NULL DEFAULT NULL,
    webhook_url        VARCHAR(255)     NULL DEFAULT NULL,
    created_at         DATETIME     NOT NULL,
    updated_at         DATETIME     NOT NULL,
    PRIMARY KEY (organization_id)
);
CREATE UNIQUE INDEX uniq_organizations_slug ON organizations (slug);
CREATE UNIQUE INDEX uniq_organizations_custom_domain ON organizations (custom_domain);
