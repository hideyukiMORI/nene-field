# ADR 0006: English-Only Repository Documentation

## Status

accepted

## Context

The NeNe ecosystem targets Japan SMB as its primary end-user market. However,
the repository documentation (README, AGENTS.md, ADRs, development guides, API specs)
serves a different audience: developers and AI agents contributing to the codebase.

Maintaining bilingual documentation doubles the maintenance burden without proportional
benefit for the code contributor audience.

Sibling products (Vault, Invoice, Clear, Profile) have adopted English-only repository
docs under equivalent ADRs.

## Decision

All repository documentation is written in **English**:
- `README.md`, `AGENTS.md`, `CLAUDE.md`
- All files under `docs/`
- Commit message type/scope
- GitHub Issue titles and PR titles

**Japanese is allowed in:**
- Commit message description and body (Japanese developer norms)
- GitHub Issue bodies and PR bodies (internal team communication)
- Admin UI locale strings (`locales/ja.json`)
- In-code comments when explaining Japan-specific business rules

## Consequences

- Reduced documentation maintenance burden
- Consistent with sibling product conventions
- Japanese speakers on the team can still use Japanese in Issues/PRs/commits

## Related

- Issue: `#1`
- Same policy as `nene-vault` ADR 0008
