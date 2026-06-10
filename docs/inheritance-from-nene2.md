# Inheritance from NENE2

NeNe Field inherits engineering governance from [NENE2](https://github.com/hideyukiMORI/NENE2).
This document is the source of truth for what is inherited, what is adapted, and what is NeNe Field–specific.

## Relationship

| Layer | Repository | Role |
| --- | --- | --- |
| Framework runtime | [NENE2](https://github.com/hideyukiMORI/NENE2) | HTTP runtime, DI, middleware, Problem Details, OpenAPI/MCP patterns |
| Application platform | **NeNe Field** (this repo) | Daily report platform — submit, approve, export, AI summary |
| Sibling products | `nene-invoice`, `nene-clear`, `nene-vault`, `nene-records` | Optional HTTP reference links (no shared DB) |

NeNe Field is a **consumer project**, not a fork of NENE2. Framework code stays in NENE2; product code stays here.

## Inherited by policy (same rules, local copies)

| Topic | Local document |
| --- | --- |
| Issue-driven workflow | `docs/workflow.md` |
| Conventional Commits | `docs/development/commit-conventions.md` |
| Self-review before PR | `docs/development/self-review.md` |
| ADR operation | `docs/development/adr.md` |
| AI agent workflow | `docs/integrations/ai-tools.md`, `AGENTS.md` |

## Inherited by reference (framework behavior)

When implementing HTTP, middleware, validation, or error responses, follow NENE2 upstream docs
unless NeNe Field records an explicit deviation in an ADR.

| Topic | NENE2 upstream |
| --- | --- |
| HTTP runtime (PSR-7/15/17) | `docs/development/http-runtime.md` |
| Middleware order and security | `docs/development/middleware-security.md` |
| Request validation layers | `docs/development/request-validation.md` |
| Problem Details errors | `docs/development/api-error-responses.md` |
| Authentication boundaries | `docs/development/authentication-boundary.md` |
| JWT middleware (`BearerTokenMiddleware`) | `docs/adr/0008-jwt-authentication.md` |
| OpenAPI conventions | `docs/integrations/openapi.md` |
| MCP tool policy | `docs/integrations/mcp-tools.md` |
| Database adapter boundaries | `docs/development/database-migrations.md` |
| Domain / use case layering | `docs/development/domain-layer.md` |
| DI and wiring | `docs/development/dependency-injection.md` |
| Configuration policy | `docs/development/configuration.md` |
| Observability / logging | `docs/development/observability.md` |
| Quality tools (PHPStan, PHP-CS-Fixer) | `docs/development/quality-tools.md` |

Install NENE2 as a Composer dependency and treat `vendor/hideyukimori/nene2/docs/` as the framework reference during development.

## NeNe Field–specific policies

These policies are defined in this repository and have no direct NENE2 equivalent.

| Topic | Local document |
| --- | --- |
| AI summary scope and privacy | `docs/adr/0007-ai-summary-policy.md` |
| File attachment storage | (TBD — Phase 1 ADR) |
| Mobile upload constraints | `docs/explanation/requirements.md` |
| Report lifecycle states | `docs/explanation/domain-model.md` |
