# NeNe Field

[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](./LICENSE)
[![PHP 8.4](https://img.shields.io/badge/PHP-8.4-777BB4?logo=php)](https://www.php.net/)
[![Status: Early Development](https://img.shields.io/badge/status-early--development-orange)]()

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
and the Vite dev server — on the fixed "90 lane" ports below.

```sh
cp .env.example .env
docker compose build
docker compose run --rm app composer install   # first run only
docker compose run --rm app composer migrations:migrate
docker compose up
```

| Service | URL |
| --- | --- |
| API (PHP/Apache) | http://localhost:9000 — health: `curl -fsS http://localhost:9000/health` |
| Admin SPA (Vite dev) | http://localhost:5190 |
| phpMyAdmin | http://localhost:9001 |
| MySQL (host) | `localhost:3309` |

The backend defaults to **MySQL** inside Docker (`DB_HOST=mysql`); the Vite dev
server proxies API paths to the `app` container. The repository's `.env` default
stays SQLite (Tier A) for non-Docker runs. Stop with `docker compose down`.

## Local port allocation (binding)

NeNe Field runs alongside sibling products on the same developer machine.
Its host-published ports are **fixed in the "90 lane"** to avoid collisions:

| Service | Host port | Env var |
| --- | --- | --- |
| PHP backend (Apache) | **9000** | `NENE_FIELD_PORT` |
| Vite dev server | **5190** | `NENE_FIELD_FRONTEND_PORT` |
| MySQL | **3309** | `NENE_FIELD_MYSQL_PORT` |
| phpMyAdmin | **9001** | `NENE_FIELD_PHPMYADMIN_PORT` |

> The previous `87xx` HTTP lane and frontend port `5187` were vacated — they belong
> to **NeNe Concierge** (`87xx`) and **NeNe Deal** (`5187`) respectively. MySQL moved
> from `3387` to `3309` to keep the whole allocation on the unique `90xx` lane and off
> any sibling's reserved `33xx` port.

### Portfolio-wide port registry

Authoritative reservation map for the NeNe portfolio. **Never reuse another app's
lane or reserved ports.** `xx` denotes the whole HTTP lane (e.g. `90xx` = 9000–9099).

| App | HTTP lane | Frontend (Vite) | MySQL | Other reserved |
| --- | --- | --- | --- | --- |
| NeNe Serve | 80xx | 5180 | 3380 | 1080, 3308, 6107 |
| NeNe Deal | 81xx | 5187 | 3310 | 6106 |
| NENE2 | 82xx | — | 3316 | — |
| NeNe Clear | 83xx | 5173 | — | — |
| NeNe Profile | 84xx | — | 3409 | — |
| NeNe Invoice | 85xx | 5185 | — | — |
| NeNe Vault | 86xx | 5186 | — | — |
| NeNe Concierge | 87xx | — | 3790 | — |
| NeNe Suite | 88xx | 5188 | 3390 | — |
| NeNe Coropus | 89xx | 5271 | 3389 | — |
| **NeNe Field** (this) | **90xx** | **5190** | **3309** | — |
| NeNe Records | 180xx | — | — | — |

## NeNe ecosystem

| Product | Repository | Domain |
| --- | --- | --- |
| **NeNe Invoice** | [`nene-invoice`](https://github.com/hideyukiMORI/nene-invoice) | Quote, invoice, payment management |
| **NeNe Clear** | [`nene-clear`](https://github.com/hideyukiMORI/nene-clear) | Payment reconciliation & dunning |
| **NeNe Profile** | [`nene-profile`](https://github.com/hideyukiMORI/nene-profile) | Bank CSV normalization |
| **NeNe Vault** | [`nene-vault`](https://github.com/hideyukiMORI/nene-vault) | Received-document archive |
| **NeNe Records** | [`nene-records`](https://github.com/hideyukiMORI/nene-records) | Flexible entity platform |
| **NeNe Field** | `nene-field` (this) | Daily report platform |
