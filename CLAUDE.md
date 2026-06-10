# CLAUDE.md — NeNe Field

Claude Code / AI agent guide for this repository. Cursor summaries live in `.cursor/rules/`.

## Source of Truth

| Purpose | Document |
| --- | --- |
| Legal & compliance (binding) | `docs/explanation/legal-compliance.md` |
| Scope contract (binding) | `docs/explanation/scope-contract.md` |
| NENE2 inheritance | `docs/inheritance-from-nene2.md` |
| Agent entry | `AGENTS.md` |
| Workflow | `docs/workflow.md` |
| Commits | `docs/development/commit-conventions.md` |
| Coding (index) | `docs/development/coding-standards.md` |
| Backend standards (binding) | `docs/development/backend-standards.md` |
| Frontend standards (binding) | `docs/development/frontend-standards.md` |
| NENE2 compliance (binding) | `docs/development/nene2-compliance.md` |
| Current tasks | `docs/todo/current.md` |
| Roadmap | `docs/roadmap.md` |
| Canonical terms | `docs/terms.md` |

## Quick Rules

- **Issue-driven**: no Issue, no code/doc change (except explicit user scope limits).
- **Branch**: `type/issue-number-summary` from `main`; never commit directly to `main`.
- **Commits**: Conventional Commits; type/scope English, description/body Japanese, include `(#issue)`.
- **PR**: purpose, changes, verification, checklist name, `Closes #n`.
- **Secrets**: never commit `.env`, tokens, or credentials.
- **Framework**: NENE2 via Composer — read `vendor/hideyukimori/nene2/docs/` for runtime patterns. NENE2 is the authoritative coding baseline; deviate only via a local ADR.
- **Coding rules (binding)**: backend layering/placement/shared-objects in `docs/development/backend-standards.md`; frontend FSD in `docs/development/frontend-standards.md`. Run `docs/review/backend.md` / `docs/review/frontend.md`. Placement/dependency/naming violations block merge.
- **Terms**: every identifier must match `docs/terms.md` exactly. Check before writing any name.
- **Legal positioning (binding)**: NeNe Field is **not** a statutory record (出勤簿/賃金台帳/法定帳簿/電帳法/施工体制台帳). No overclaim. Run `docs/review/legal-compliance.md` for any change to report fields, audit, AI, retention, export, or user-facing copy.

## Product Direction

Mobile-first daily report platform for Japan SMB. Field workers submit reports from smartphones; managers review, approve, and export data. AI summary reduces manager review burden. Optional integration with `nene-invoice` for billable hours.

## Local stack

| Service | URL | env var |
| --- | --- | --- |
| API | http://localhost:9000 | `NENE_FIELD_PORT` |
| phpMyAdmin | http://localhost:9001 | `NENE_FIELD_PHPMYADMIN_PORT` |
| MySQL (host) | localhost:3309 | `NENE_FIELD_MYSQL_PORT` |
| Frontend dev | http://localhost:5190 | `NENE_FIELD_FRONTEND_PORT` |

Health check: `curl -fsS http://localhost:9000/health`

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
