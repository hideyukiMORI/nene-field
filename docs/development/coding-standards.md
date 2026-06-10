# Coding Standards

NeNe Field inherits coding standards from NENE2. This document is the quick index;
the **binding, detailed** standards live in:

- **Backend (binding):** [`backend-standards.md`](./backend-standards.md)
- **Frontend (binding):** [`frontend-standards.md`](./frontend-standards.md)
- **Naming (binding):** [`naming-conventions.md`](./naming-conventions.md) → exact strings in [`../terms.md`](../terms.md)
- **NENE2 compliance:** [`nene2-compliance.md`](./nene2-compliance.md)
- Self-review: [`../review/backend.md`](../review/backend.md), [`../review/frontend.md`](../review/frontend.md)

Framework baseline (authoritative): NENE2 `docs/development/` (`vendor/hideyukimori/nene2/`).
Deviate from NENE2 only via a local ADR. This file records NeNe Field–specific
highlights and adaptations.

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

- TypeScript strict mode; mobile-first (375px viewport baseline).
- FSD layering `app → pages → features → entities → shared`.
- State: TanStack Query for server data; React Router `searchParams` for URL
  state; `useState` for ephemeral UI. **No Redux/Zustand/Jotai without an ADR.**
- Styles: Tailwind CSS with semantic tokens in `shared/ui/theme/`.
- No `fetch` outside `src/shared/api/client.ts`.

Full binding rules: [`frontend-standards.md`](./frontend-standards.md).

## File Attachment Handling

- Max 5 files per report; max 5 MB per file.
- Server-side compression to ≤ 1 MB for JPEG/PNG via GD or Imagick.
- Storage path never included in API responses.
- SHA-256 computed on upload and verified on download.

## AI Integration

- AI API calls are isolated in `src/NeneField/AiSummary/` namespace.
- Only `reports.body` is passed to the prompt; no PII metadata.
- Errors from AI API are caught and logged; report submission must not fail due to AI errors.
