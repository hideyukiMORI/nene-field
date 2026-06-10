# ADR 0005: Product Identity — NeNe Field

## Status

accepted

## Context

The product name and identity must be established before any marketing, README badges,
or public repository are created.

The name must:
- fit the NeNe ecosystem naming convention (`nene-*`)
- be short, memorable, and internationalization-friendly
- reflect the core use case (field work, daily reporting, on-site records)

## Decision

The product is named **NeNe Field**.

- Repository: `nene-field`
- GitHub org: `hideyukiMORI`
- PHP namespace: `NeneField\`
- Composer package: `hideyukimori/nene-field` (when published)
- npm package (frontend): `@hideyukimori/nene-field` (when published)

The name "Field" reflects:
- Field workers (現場スタッフ) as the primary users
- Field reports (現場報告) as the primary artifact
- Field → back-office data flow as the core value

Do not use the product name of competitors or third-party tools in repository docs
(same policy as `nene-vault` ADR 0013).

## Consequences

- All identifiers in code, DB, API, and docs must use `NeneField` or `nene-field` as prefix/namespace
- Marketing materials may use localized names but the repository identity is fixed

## Related

- Issue: `#1`
