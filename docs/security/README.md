# Security — NeNe Field

A map of the security **design**: the trust boundaries, the control that guards each
one, where the control is enforced in code, and the test that proves it. This is a
design index, not a penetration-test record — nothing here targets a production host.
A live-fire assessment (T3 L3/L4) is future work pending maintainer go-ahead; when
NeNe Field runs an authorized self-assessment it lands here as a dated report
alongside this index, following the fleet precedent of NeNe Vault's dated assessments.

**Scope note.** This directory documents controls that are **implemented and
verified**. It deliberately does not enumerate un-remediated weaknesses or
exploitation detail — this repository is public. Hardening still in flight is tracked
out-of-band with the fleet hub.

**Positioning.** NeNe Field is **not** a statutory record (出勤簿 / 賃金台帳 / 法定帳簿 /
電帳法 / 施工体制台帳). Nothing in this document should be read as a claim of
statutory-grade retention or evidentiary weight — see
[`../explanation/legal-compliance.md`](../explanation/legal-compliance.md) (binding).

## Trust boundaries

Three surfaces, decreasing exposure:

1. **Unauthenticated** — `GET /health` and `POST /auth/login` only. No credential is
   presented; every other route requires a verified bearer token. This is the
   untrusted surface and it is deliberately tiny.
2. **Authenticated tenant user** — the SPA (`frontend/`) and the tenant API. JWT bearer
   plus role, and **every tenant-scoped request is checked against the resolved
   organization**. Three roles: `submitter` (mobile submission surface), `approver`,
   `admin`.
3. **Cross-tenant operator** — `superadmin` only. Token carries no org (`org: null`)
   and is the only principal allowed to act outside a single organization.

## Controls index

Every path below is verified to exist in this repository at the commit that
introduced this document.

### Authentication

| Control | Enforced in | Proven by |
| --- | --- | --- |
| Bearer token required on every non-public path; public paths are an explicit allowlist (health, login). | `src/Auth/AuthServiceProvider.php` (`BearerTokenMiddleware` + `LocalBearerTokenVerifier`, fleet runtime) | `tests/Auth/AuthContextTest.php` |
| JWT secret resolution is **fail-closed in production**: the development secret is a constant in this public repository, so it is only usable behind the fleet resolver's dev opt-in. | `src/Auth/AuthServiceProvider.php` → `Nene2\Auth\GuardedJwtSecretResolver` | fleet-level (`GuardedJwtSecretResolver`) |
| Password verification is constant-time via `password_verify`; an unknown user, a bad password, and an **inactive** user are indistinguishable at the boundary (one failure path). | `src/Auth/LoginUseCase.php` | `tests/Auth/LoginUseCaseTest.php` |
| Passwords are hashed with **bcrypt cost ≥ 12** (NF9) on both create and change. | `src/User/CreateUserUseCase.php`, `src/Auth/ChangePasswordHandler.php` | `tests/Auth/ChangePasswordUseCaseTest.php`, `tests/Auth/ChangePasswordHandlerTest.php` |
| Minimum password length enforced at the boundary (8 characters). | `src/Auth/ChangePasswordHandler.php` | `tests/Auth/ChangePasswordHandlerTest.php` |

### Tenant isolation

| Control | Enforced in | Proven by |
| --- | --- | --- |
| The organization is resolved per request by a pluggable strategy — env (single-tenant / Tier A) or subdomain. | `src/Organization/Resolution/OrgResolverMiddleware.php`, `EnvResolutionStrategy.php`, `SubdomainResolutionStrategy.php` | `tests/Organization/Resolution/OrgResolverMiddlewareTest.php` |
| **Fail-closed org guard**: a token whose `org` claim does not match the resolved organization is rejected `403`. A token with `org: null` passes only when the role is `superadmin`; any other roleless-org token is rejected. | `src/Auth/OrgGuardMiddleware.php` | `tests/Auth/OrgGuardMiddlewareTest.php` |
| Cross-tenant reads are superadmin-only; a non-superadmin asking for another organization is refused rather than served. | `src/Organization/GetOrganizationHandler.php`, `ListOrganizationsHandler.php`, `CreateOrganizationHandler.php` | `tests/Organization/` |
| Role escalation to `superadmin` is not assignable through the user API. | `src/User/CreateUserUseCase.php`, `src/User/UpdateUserUseCase.php`, `src/User/RoleNotAssignableException.php` | `tests/User/` |
| Design contract for the above (binding). | [`../development/multi-tenancy.md`](../development/multi-tenancy.md) | — |

### Authorization on reports

| Control | Enforced in | Proven by |
| --- | --- | --- |
| A submitter may edit or delete **only their own** draft / rejected report. | `src/Report/UpdateReportUseCase.php`, `src/Report/DeleteReportUseCase.php` | `tests/Report/` |
| A report a caller may not see returns **404, not 403** — existence is not disclosed to a non-owner submitter. | `src/Report/ReportNotFoundException.php` | `tests/Report/` |
| Frontend gates are **fail-closed** and cosmetic-only: no token → login shell; the API remains the source of truth. | `frontend/src/app/auth-gate.tsx` | `frontend/e2e/sign-in.spec.ts`, `frontend/src/features/sign-in/model/use-sign-in.test.ts` |
| The submitter mobile surface is selected from the resolved role, defaulting to the admin shell only for a resolved non-submitter. | `frontend/src/app/use-submitter-surface.ts` | `frontend/e2e/mobile-report-detail.spec.ts`, `frontend/e2e/report-review.spec.ts` |

### Audit trail

| Control | Enforced in | Proven by |
| --- | --- | --- |
| Mutations record a **sanitized** before/after snapshot — the payload is a `*Response::toArray()` projection, so secrets never enter the audit row. | `src/AuditEvent/AuditRecorderInterface.php`, `src/AuditEvent/AuditEvent.php` | `tests/AuditEvent/AuditRecorderTest.php` |
| The write and its audit row **commit in the same transaction** (ADR 0014) — a mutation cannot land unaudited. | `src/User/UserRepositoryInterface.php`, `src/Organization/OrganizationRepositoryInterface.php`, `src/Attachment/UploadAttachmentUseCase.php` | `tests/AuditEvent/AuditRecorderTransactionTest.php` |
| Design contract for the above (binding). | [`../development/audit-logging.md`](../development/audit-logging.md) | — |

### Secret handling

| Control | Enforced in | Proven by |
| --- | --- | --- |
| The per-organization AI credential (`ai_api_key`) and its endpoint are **never modelled in, nor returned by, the organization API**, and are not read or written by the repository used by the API. | `src/Organization/OrganizationResponse.php`, `src/Organization/OrganizationRepositoryInterface.php` | `tests/Organization/` |
| `password_hash` is never part of a user API projection. | `src/User/UserResponse.php` | `tests/User/` |
| No secret is committed: `.env` is ignored and only `.env.example` (keys, no values) is tracked. | `.gitignore`, `.env.example` | — |

### File attachments

| Control | Enforced in | Proven by |
| --- | --- | --- |
| MIME type allowlist (map of accepted type → canonical extension); an unsupported type is refused rather than stored. | `src/Attachment/AttachmentConstraints.php`, `src/Attachment/UnsupportedAttachmentTypeExceptionHandler.php` | `tests/Attachment/AttachmentManagementTest.php` |
| Per-file size cap (5 MiB) and per-report count cap (5 files), surfaced as `413`. | `src/Attachment/AttachmentConstraints.php`, `AttachmentTooLargeException.php`, `TooManyAttachmentsException.php` | `tests/Attachment/AttachmentManagementTest.php` |
| **Path traversal is refused at the storage boundary**: a storage key that is empty, contains `..`, or is absolute is rejected — the client never chooses a filesystem path. | `src/Attachment/LocalAttachmentStorage.php` | `tests/Attachment/LocalAttachmentStorageTest.php` |
| Blob and metadata are consistent by construction: metadata + audit commit in one transaction and the on-disk file is removed if that transaction fails (an orphaned blob is preferred to a dangling row). | `src/Attachment/UploadAttachmentUseCase.php`, `src/Attachment/DeleteAttachmentUseCase.php` | `tests/Attachment/AttachmentManagementTest.php` |

### Export surface

| Control | Enforced in | Proven by |
| --- | --- | --- |
| **Spreadsheet formula injection is neutralised** in string cells, so report content cannot become an executable formula in the downloaded CSV. | `src/Export/ReportCsvFormatter.php`, `src/AuditEvent/AuditEventCsvFormatter.php` (fleet `CsvWriter` defaults) | `tests/Export/ReportCsvFormatterTest.php`, `tests/AuditEvent/AuditEventCsvFormatterTest.php` |
| Export is an audited action (`report.exported`), not a silent read. | `src/Export/ExportReportsCsvHandler.php` | `tests/Export/ReportExportTest.php` |

### Error surface

| Control | Enforced in | Proven by |
| --- | --- | --- |
| Errors are RFC 9457 Problem Details with a stable type URI; handlers map domain exceptions to statuses instead of leaking internals. | `src/Http/RuntimeServiceProvider.php` (`ProblemDetailsResponseFactory`), e.g. `src/Attachment/UnsupportedAttachmentTypeExceptionHandler.php`, `src/User/RoleNotAssignableExceptionHandler.php` | `tests/Error/` |
| The client normalises Problem Details into one error type and classifies retryability, so a transport failure is not mistaken for an auth failure. | `frontend/src/shared/api/errors.ts` | `frontend/src/shared/api/errors.test.ts` |
| A `401` on an authenticated request **clears the stored token** (fail-closed → re-login). | `frontend/src/shared/api/client.ts` | `frontend/src/shared/api/client.test.ts` |

### Browser-side token handling

| Control | Enforced in | Proven by |
| --- | --- | --- |
| The bearer token lives in **`sessionStorage`** (tab-scoped, cleared on tab close) via the fleet `createSessionTokenStore` — never `localStorage`. Cleared on sign-out and on `401`. | `frontend/src/shared/api/client.ts` | `frontend/src/shared/api/client.test.ts` |
| A single module is the only caller of `fetch`, so there is one choke point for auth headers rather than per-feature request code. | `frontend/src/shared/api/client.ts` | `frontend/src/shared/api/client.test.ts` |

## Supply chain

| Control | Where |
| --- | --- |
| Dependency vulnerability gate runs in CI (`audit-ci`, ID-limited allowlist with a written rationale, expiry, and release condition per entry). | [`../development/dependency-audit.md`](../development/dependency-audit.md), `frontend/audit-ci.jsonc` |
| Branch protection requires `backend-check`, `frontend-check`, and `frontend-e2e`; admins are not exempt. | GitHub branch protection (verified via API, not asserted here) |

## What is deliberately not here

- **No un-remediated weakness list and no exploitation detail** — public repository.
- **No infrastructure specifics** (hosts, paths, credentials rotation procedure) — see
  the daily-report convention §8 rule this follows.
- **No live-fire assessment result** — none has been run for NeNe Field. This document
  makes no claim about behaviour under attack; it documents design and the tests that
  guard it.
