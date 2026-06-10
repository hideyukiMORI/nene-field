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
| **Product vision** | [`docs/explanation/product-vision.md`](./docs/explanation/product-vision.md) |
| **Feature list** | [`docs/explanation/features.md`](./docs/explanation/features.md) |
| **Page list** | [`docs/explanation/pages.md`](./docs/explanation/pages.md) |
| **API list** | [`docs/openapi/openapi.yaml`](./docs/openapi/openapi.yaml) |
| **Domain model** | [`docs/explanation/domain-model.md`](./docs/explanation/domain-model.md) |
| **Requirements** | [`docs/explanation/requirements.md`](./docs/explanation/requirements.md) |
| **Sibling integration** | [`docs/integrations/sibling-products.md`](./docs/integrations/sibling-products.md) |
| **Agents** | [`AGENTS.md`](./AGENTS.md) |
| **Roadmap** | [`docs/roadmap.md`](./docs/roadmap.md) |

## Quick start (Docker)

> **Status: not yet implemented.** The repository is in the governance and documentation phase.
> Implementation begins after the OpenAPI skeleton and DB schema are agreed upon.

```sh
cp .env.example .env
docker compose up
```

## Local port allocation (binding)

NeNe Field runs alongside sibling products on the same developer machine.
Its host-published ports are **fixed in the "87 lane"** to avoid collisions:

| Service | Host port | Env var |
| --- | --- | --- |
| PHP backend (Apache) | **8700** | `NENE_FIELD_PORT` |
| Vite dev server | **5187** | `NENE_FIELD_FRONTEND_PORT` |
| MySQL | **3387** | `NENE_FIELD_MYSQL_PORT` |
| phpMyAdmin | **8701** | `NENE_FIELD_PHPMYADMIN_PORT` |

### Portfolio-wide port registry

| App | HTTP | Frontend | DB |
| --- | --- | --- | --- |
| NENE2 | 8200 | — | 3316 |
| NeNe Clear | 8384 | 5383 | 3383 |
| NeNe Profile | 8490 | 5185 (planned) | 3409 |
| NeNe Invoice | 8510 | 5185 | 3585 |
| NeNe Vault | 8600 | 5186 | 3386 |
| **NeNe Field** | **8700** | **5187** | **3387** |
| NeNe Suite | 8800 | 5188 | 3389 |
| NeNe Records | 18082 | 18084 | 13308 |

## NeNe ecosystem

| Product | Repository | Domain |
| --- | --- | --- |
| **NeNe Invoice** | [`nene-invoice`](https://github.com/hideyukiMORI/nene-invoice) | Quote, invoice, payment management |
| **NeNe Clear** | [`nene-clear`](https://github.com/hideyukiMORI/nene-clear) | Payment reconciliation & dunning |
| **NeNe Profile** | [`nene-profile`](https://github.com/hideyukiMORI/nene-profile) | Bank CSV normalization |
| **NeNe Vault** | [`nene-vault`](https://github.com/hideyukiMORI/nene-vault) | Received-document archive |
| **NeNe Records** | [`nene-records`](https://github.com/hideyukiMORI/nene-records) | Flexible entity platform |
| **NeNe Field** | `nene-field` (this) | Daily report platform |
