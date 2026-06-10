# ADR 0001: Inherit NENE2 Governance

## Status

accepted

## Context

NeNe Field is a new product in the NeNe ecosystem. The sibling products (Invoice,
Clear, Vault, Records, Profile) all inherit their engineering governance from NENE2,
including issue-driven workflow, Conventional Commits, ADR methodology, PHP coding
standards, and quality tooling (PHPStan level 8, PHP-CS-Fixer, PHPUnit).

Maintaining separate governance standards per product would create unnecessary divergence
and increase the maintenance burden for the maintainer.

## Decision

NeNe Field inherits NENE2's engineering governance by policy. Specifically:

- Issue-driven workflow (no code without an Issue)
- Conventional Commits format
- Branch naming `type/issue-number-summary`; no direct commits to `main`
- ADR methodology for architectural decisions
- PHP 8.4, strict_types, PSR-12, PHPStan level 8, PHP-CS-Fixer
- NENE2 as runtime framework via Composer
- English-only repository documentation (see ADR 0006)
- Handler → UseCase → RepositoryInterface → PdoRepository layering
- Problem Details (RFC 9457) for API errors
- OpenAPI 3.1 as the API contract
- Multi-tenant isolation via `organization_id` on every tenanted table (see ADR 0004)
- JWT authentication via NENE2's `BearerTokenMiddleware`

Deviations from NENE2 governance require a new ADR in this repository.

## Consequences

- Reduced per-product governance maintenance
- Familiar patterns for contributors who know other NeNe products
- NeNe Field–specific decisions must be recorded here so they are not confused with NENE2 upstream behavior

## Related

- Issue: `#1`
- Supersedes: none
- See also: `docs/inheritance-from-nene2.md`
