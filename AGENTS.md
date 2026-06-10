# Agent / AI Guide

Entry point for AI agents working on **NeNe Field** (public repo `nene-field`).

## Domain (read first)

| Product | Repository | Domain |
| --- | --- | --- |
| **NeNe Invoice** | `nene-invoice` | Quote, invoice, payment management |
| **NeNe Clear** | `nene-clear` | Payment reconciliation & dunning |
| **NeNe Profile** | `nene-profile` | Bank CSV normalization |
| **NeNe Vault** | `nene-vault` | Received-document archive |
| **NeNe Records** | `nene-records` | Flexible entity platform |
| **NeNe Field** | `nene-field` (this) | Daily report platform |

See [ADR 0002](docs/adr/0002-separate-from-sibling-products.md).

## Read First

- **Canonical terms & 用語一覧 — the single source of truth (binding):** `docs/terms.md` ← **START HERE for any identifier or term.** Exact match only; typos block merge (`terms.md §11`).
- **Scope contract (binding):** `docs/explanation/scope-contract.md`
- **Legal & compliance positioning (binding):** `docs/explanation/legal-compliance.md` ← what the product is **NOT**
- **Product vision:** `docs/explanation/product-vision.md`
- **Requirements:** `docs/explanation/requirements.md`
- **Feature list:** `docs/explanation/features.md`
- **Page list:** `docs/explanation/pages.md`
- **Domain model:** `docs/explanation/domain-model.md`
- **Glossary / 用語一覧:** consolidated into `docs/terms.md §10` (glossary.md is now a pointer)
- **Scope boundary:** `docs/explanation/scope-boundary.md`
- **Naming rules (binding):** `docs/development/naming-conventions.md`
- **Backend standards (binding):** `docs/development/backend-standards.md`
- **Multi-tenancy (binding):** `docs/development/multi-tenancy.md` ← tenant isolation is a security premise
- **Audit logging (binding):** `docs/development/audit-logging.md` ← every mutation records who/what + before/after, same transaction
- **Frontend standards (binding):** `docs/development/frontend-standards.md`
- **Coding standards (index):** `docs/development/coding-standards.md`
- **NENE2 compliance (binding):** `docs/development/nene2-compliance.md`
- **Sibling integration:** `docs/integrations/sibling-products.md`
- **NENE2 inheritance map:** `docs/inheritance-from-nene2.md`
- **Current work:** `docs/todo/current.md`
- **Roadmap:** `docs/roadmap.md`

## Operating Rules

- Issue-driven; no direct commits to `main`
- Do **not** add payroll calculation or statutory labor management — out of scope
- Do **not** add invoice issuance — **`nene-invoice`**
- Do **not** add bank reconciliation — **`nene-clear`**
- Do **not** add document archiving as SSOT — **`nene-vault`**
- Do **not** position the product as a statutory record (出勤簿/賃金台帳/法定帳簿/電帳法) — `docs/explanation/legal-compliance.md`
- **No overclaim** in code, UI, or docs — prohibited claims are listed in `legal-compliance.md` §10
- **Follow NENE2 conventions** — `docs/development/nene2-compliance.md`
- Namespace: `NeneField\`; money: integer cents
- **Repository docs: English only** (ADR 0006)

## Framework

[NENE2](https://github.com/hideyukiMORI/NENE2) via Composer (`vendor/hideyukimori/nene2/`).
