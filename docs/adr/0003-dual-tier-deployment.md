# ADR 0003: Dual-Tier Deployment

## Status

accepted

## Context

The target operators are Japan SMB with 5–100 employees. Many use shared PHP hosting
(さくらインターネット, ロリポップ, XServer) and cannot run Docker. A Docker-only product
would exclude this segment.

At the same time, developers and larger operators need Docker for local development
and VPS deployment.

Sibling products (Invoice, Vault) have successfully shipped both tiers from a single
codebase.

## Decision

NeNe Field ships both deployment tiers from the same codebase:

**Tier A — Shared hosting (PHP shared hosting, FTP upload)**
- Release ZIP with web installer (`install.php`)
- SQLite as default database (no MySQL required)
- No Docker dependency

**Tier B — Docker / VPS**
- `docker-compose.yml` with PHP 8.4 + MySQL + Vite dev server
- `.env`-driven configuration

Both tiers use the same PHP source, OpenAPI contract, and feature set.
Feature flags or environment variables differentiate capabilities (e.g.,
AI summary requires external API key; email notifications require SMTP).

## Consequences

- No Docker-specific code paths in business logic
- Absolute file paths must use env-configured storage roots, not `__DIR__`-relative assumptions
- SQLite and MySQL must be tested in CI

## Related

- Issue: `#1`
- See also: `docs/explanation/requirements.md`
