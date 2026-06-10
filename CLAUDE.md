# CLAUDE.md — NeNe Field

Claude Code / AI agent guide for this repository. Cursor summaries live in `.cursor/rules/`.

## Source of Truth

| Purpose | Document |
| --- | --- |
| NENE2 inheritance | `docs/inheritance-from-nene2.md` |
| Agent entry | `AGENTS.md` |
| Workflow | `docs/workflow.md` |
| Commits | `docs/development/commit-conventions.md` |
| Coding | `docs/development/coding-standards.md` |
| Current tasks | `docs/todo/current.md` |
| Roadmap | `docs/roadmap.md` |
| Canonical terms | `docs/terms.md` |

## Quick Rules

- **Issue-driven**: no Issue, no code/doc change (except explicit user scope limits).
- **Branch**: `type/issue-number-summary` from `main`; never commit directly to `main`.
- **Commits**: Conventional Commits; type/scope English, description/body Japanese, include `(#issue)`.
- **PR**: purpose, changes, verification, checklist name, `Closes #n`.
- **Secrets**: never commit `.env`, tokens, or credentials.
- **Framework**: NENE2 via Composer — read `vendor/hideyukimori/nene2/docs/` for runtime patterns.
- **Terms**: every identifier must match `docs/terms.md` exactly. Check before writing any name.

## Product Direction

Mobile-first daily report platform for Japan SMB. Field workers submit reports from smartphones; managers review, approve, and export data. AI summary reduces manager review burden. Optional integration with `nene-invoice` for billable hours.

## Local stack

| Service | URL | env var |
| --- | --- | --- |
| API | http://localhost:8700 | `NENE_FIELD_PORT` |
| phpMyAdmin | http://localhost:8701 | `NENE_FIELD_PHPMYADMIN_PORT` |
| MySQL (host) | localhost:3387 | `NENE_FIELD_MYSQL_PORT` |
| Frontend dev | http://localhost:5187 | `NENE_FIELD_FRONTEND_PORT` |

Health check: `curl -fsS http://localhost:8700/health`

## Verification

```bash
# Full quality gates (run before every PR)
composer check

# Individual steps
composer test
composer analyse
composer cs
composer openapi
npm run check --prefix frontend
```
