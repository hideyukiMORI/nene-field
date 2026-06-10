# Sibling Product Integration

NeNe Field integrates with sibling products via **HTTP reference links only**.
No shared database. No foreign keys across product boundaries.

## Integration Map

| From NeNe Field | Direction | To | Link type |
| --- | --- | --- | --- |
| `reports.invoice_work_order_id` | → | `nene-invoice` `/work-orders/{id}` | Optional HTTP reference (read) |
| `reports.records_entity_id` | → | `nene-records` `/entities/{id}` | Optional HTTP reference (read) |

## NeNe Invoice — Work Order Link

**Use case:** A field worker submits a report for work done on a specific billed project.
The report carries `invoice_work_order_id` so managers can cross-reference field work
against billing records.

**How it works:**
- Submitter selects a work order from a dropdown (populated by calling Invoice API).
- `invoice_work_order_id` is stored as a plain string on the report (no JOIN).
- Report detail page fetches and displays the work order title via an authenticated HTTP call to Invoice API.
- CSV export includes `invoice_work_order_id` for manual reconciliation.

**Required Invoice endpoint:** `GET /work-orders/{id}` — read-only.

**What NeNe Field must NOT do:**
- Write to any Invoice endpoint
- Store Invoice data locally (cache display data only, never as SSOT)
- Break if Invoice is unavailable (graceful degradation: show the ID, not a hard error)

## NeNe Records — Entity Link

**Use case:** A field worker links a daily report to a project, site, or asset entity
managed in NeNe Records.

**How it works:**
- `records_entity_id` stored as string on the report.
- Report detail fetches entity name from Records API for display.
- Graceful degradation if Records is unavailable.

## Auth between products

Each product maintains its own JWT secret and user store. Cross-product API calls
use a **service bearer token** configured per installation via environment variables
(e.g., `NENE_FIELD_INVOICE_API_KEY`). Token is stored in `.env`, never committed.

## What NOT to do

- Never share a PDO connection or database with a sibling product.
- Never import sibling product models or schema into NeNe Field.
- Never hard-fail if a sibling product is offline.
