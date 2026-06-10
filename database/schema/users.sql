-- Schema snapshot: users (operator accounts). Reference only; Phinx migrations are
-- the source of truth. Tenant-scoped; email uniqueness is per organization.
CREATE TABLE users (
    user_id         CHAR(36)     NOT NULL,
    organization_id CHAR(36)     NOT NULL,
    name            VARCHAR(255) NOT NULL,
    email           VARCHAR(255) NOT NULL,
    password_hash   VARCHAR(255) NOT NULL, -- bcrypt; never in API/audit responses
    role            VARCHAR(20)  NOT NULL, -- submitter | approver | admin | superadmin
    is_active       BOOLEAN      NOT NULL DEFAULT 1,
    created_at      DATETIME     NOT NULL,
    updated_at      DATETIME     NOT NULL,
    PRIMARY KEY (user_id)
);
CREATE UNIQUE INDEX uniq_users_email_org ON users (organization_id, email);
CREATE INDEX idx_users_organization_id ON users (organization_id);
