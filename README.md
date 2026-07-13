# NeNe Field

[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](./LICENSE)
[![PHP 8.4](https://img.shields.io/badge/PHP-8.4-777BB4?logo=php)](https://www.php.net/)

**Daily report platform — self-hosted for Japan SMB.**

**NeNe Field** lets field workers submit daily reports from their smartphones and
lets managers review, approve, and export work records — without heavy workflows
or payroll complexity. Built on [NENE2](https://github.com/hideyukiMORI/NENE2),
shared hosting or Docker.

> **Separate product.** NeNe Field does **not** issue invoices
> ([`nene-invoice`](https://github.com/hideyukiMORI/nene-invoice)),
> reconcile bank deposits ([`nene-clear`](https://github.com/hideyukiMORI/nene-clear)),
> or archive received documents ([`nene-vault`](https://github.com/hideyukiMORI/nene-vault)).
> See [ADR 0002](./docs/adr/0002-separate-from-sibling-products.md).

## Goals

- **Mobile-first submission** — smartphone form, photo attachments, one-tap send
- **Approval workflow** — approve / reject with comment; notification via email / webhook
- **Manager list view** — filter by staff, date, tag, approval status
- **CSV export** — hand data to payroll, billing, or accounting
- **AI summary** — long text → one-line summary + keyword tags for fast review
- **Audit trail** — immutable log of every submission, approval, and edit
- **NENE ecosystem links** — optional HTTP reference to `nene-invoice` work orders

## Documentation (read first)

| Topic | Document |
| --- | --- |
| **Scope contract (GOAL / DO / DON'T)** | [`docs/explanation/scope-contract.md`](./docs/explanation/scope-contract.md) |
| **Legal & compliance (binding)** | [`docs/explanation/legal-compliance.md`](./docs/explanation/legal-compliance.md) |
| **Product vision** | [`docs/explanation/product-vision.md`](./docs/explanation/product-vision.md) |
| **Feature list** | [`docs/explanation/features.md`](./docs/explanation/features.md) |
| **Page list** | [`docs/explanation/pages.md`](./docs/explanation/pages.md) |
| **API list** | [`docs/openapi/openapi.yaml`](./docs/openapi/openapi.yaml) |
| **Domain model** | [`docs/explanation/domain-model.md`](./docs/explanation/domain-model.md) |
| **Requirements** | [`docs/explanation/requirements.md`](./docs/explanation/requirements.md) |
| **Backend standards (binding)** | [`docs/development/backend-standards.md`](./docs/development/backend-standards.md) |
| **Frontend standards (binding)** | [`docs/development/frontend-standards.md`](./docs/development/frontend-standards.md) |
| **Sibling integration** | [`docs/integrations/sibling-products.md`](./docs/integrations/sibling-products.md) |
| **Agents** | [`AGENTS.md`](./AGENTS.md) |
| **Roadmap** | [`docs/roadmap.md`](./docs/roadmap.md) |

## Quick start (Docker)

`compose.yaml` brings up the full local stack — PHP backend, MySQL, phpMyAdmin,
and the Vite dev server — on the fixed "92 lane" ports below.

```sh
cp .env.example .env
docker compose build
docker compose run --rm app composer install   # first run only
docker compose run --rm app composer migrations:migrate
docker compose up
```

| Service | URL |
| --- | --- |
| API (PHP/Apache) | http://localhost:9200 — health: `curl -fsS http://localhost:9200/health` |
| Admin SPA (Vite dev) | http://localhost:5192 |
| phpMyAdmin | http://localhost:9201 |
| MySQL (host) | `localhost:3309` |

The backend defaults to **MySQL** inside Docker (`DB_HOST=mysql`); the Vite dev
server proxies API paths to the `app` container. The repository's `.env` default
stays SQLite (Tier A) for non-Docker runs. Stop with `docker compose down`.

## Local ports

NeNe Field runs alongside sibling products on the same developer machine; its
host-published ports are fixed in the **"92" lane** so several apps can run
locally side by side (full policy: [`CLAUDE.md`](./CLAUDE.md#local-stack)).
Override via `NENE_FIELD_*` in `.env`.

| Service | Port |
| --- | --- |
| PHP backend (Apache) | 9200 |
| Vite dev server (frontend HMR) | 5192 |
| MySQL (Docker) | 3309 |
| phpMyAdmin | 9201 |

## Status

| Phase | Scope | Status |
| --- | --- | --- |
| 1 | Core Report API — multi-tenancy, audit logging, report CRUD/submission lifecycle, org/user management, templates, attachments (#21–#37) | ✅ |
| 2 | Manager UI + Export — backend API (CSV export, audit log API) done; admin UI (auth, report list/detail/review/submit, templates, users, audit viewer, export, settings, hi-fi redesign) done; Docker Compose dev env done | 🔄 In progress — Storybook + Playwright e2e outstanding |
| 3 | AI Summary + Notifications | ⏳ Planned |
| 4 | Ecosystem Links | ⏳ Planned |

Kept in sync with [`docs/todo/current.md`](./docs/todo/current.md).

## NeNe ecosystem

| Product | Repository | Domain |
| --- | --- | --- |
| **NeNe Invoice** | [`nene-invoice`](https://github.com/hideyukiMORI/nene-invoice) | Quote, invoice, payment management |
| **NeNe Clear** | [`nene-clear`](https://github.com/hideyukiMORI/nene-clear) | Payment reconciliation & dunning |
| **NeNe Profile** | [`nene-profile`](https://github.com/hideyukiMORI/nene-profile) | Bank CSV normalization |
| **NeNe Vault** | [`nene-vault`](https://github.com/hideyukiMORI/nene-vault) | Received-document archive |
| **NeNe Records** | [`nene-records`](https://github.com/hideyukiMORI/nene-records) | Flexible entity platform |
| **NeNe Field** | `nene-field` (this) | Daily report platform |
