# Coding Standards

NeNe Field inherits coding standards from NENE2. This document records NeNe Field–specific
additions and adaptations.

## PHP

- PHP 8.4, `declare(strict_types=1)` in every file.
- PSR-12 formatting enforced by PHP-CS-Fixer.
- PHPStan level 8.
- Namespace: `NeneField\`.
- Layering: `Handler → UseCase → RepositoryInterface → PdoXxxRepository`.
- Money: **integer cents** (no floats).
- Identifiers: must match `docs/terms.md` exactly.

See NENE2 `docs/development/coding-standards.md` for the full baseline.

## Frontend (React + Vite)

- TypeScript strict mode.
- Components are mobile-first (375px viewport baseline).
- State: TanStack Query for server data; Zustand for local UI state.
- Styles: Tailwind CSS.
- No fetch outside `src/shared/api/client.ts`.

## File Attachment Handling

- Max 5 files per report; max 5 MB per file.
- Server-side compression to ≤ 1 MB for JPEG/PNG via GD or Imagick.
- Storage path never included in API responses.
- SHA-256 computed on upload and verified on download.

## AI Integration

- AI API calls are isolated in `src/NeneField/AiSummary/` namespace.
- Only `reports.body` is passed to the prompt; no PII metadata.
- Errors from AI API are caught and logged; report submission must not fail due to AI errors.
