# AI Tools Policy

NeNe Field uses AI assistance in development and in the product itself.

## Development AI (Cursor / Claude Code)

- AI agents follow `AGENTS.md` and `CLAUDE.md` rules.
- No secrets, `.env`, or credentials are committed.
- AI-generated code must pass `composer check` before PR.

## Product AI Feature — AI Summary

The AI summary feature (F-11) sends report body text to an external LLM API.

- **Opt-in per organization.** Disabled by default.
- **Data minimization.** Only `reports.body` is sent — no user name, date, or metadata.
- **Operator-configured API key.** NeNe Field does not bundle a default key.
- **Audit event** recorded when summary is generated or cleared.

See [ADR 0007](../adr/0007-ai-summary-policy.md) for the full policy.
