-- Schema snapshot: audit_events (immutable trail; ADR 0014). Append-only — no
-- UPDATE / hard DELETE. before_json/after_json hold sanitized snapshots (no
-- secrets); the API exposes them as `before`/`after`.
CREATE TABLE audit_events (
    event_id        CHAR(36)     NOT NULL,
    organization_id CHAR(36)     NOT NULL,
    actor_id        CHAR(36)         NULL DEFAULT NULL, -- null = system action
    event_name      VARCHAR(64)  NOT NULL,             -- {entity}.{verb} (terms.md §8)
    entity_type     VARCHAR(64)  NOT NULL,
    entity_id       CHAR(36)     NOT NULL,
    before_json     TEXT             NULL DEFAULT NULL, -- sanitized; null for create
    after_json      TEXT             NULL DEFAULT NULL, -- sanitized; null for delete
    request_id      VARCHAR(64)      NULL DEFAULT NULL, -- correlation with app logs
    occurred_at     DATETIME     NOT NULL,
    PRIMARY KEY (event_id)
);
CREATE INDEX idx_audit_events_organization_id ON audit_events (organization_id);
CREATE INDEX idx_audit_events_entity ON audit_events (entity_type, entity_id);
