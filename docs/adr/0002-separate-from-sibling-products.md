# ADR 0002: Separate from Sibling Products

## Status

accepted

## Context

The NeNe ecosystem contains multiple products that cover adjacent domains:
Invoice (billing), Clear (reconciliation), Vault (received-document archive),
Records (entity platform), Profile (bank CSV normalization).

There is a temptation to expand NeNe Field to cover payroll, statutory timekeeping,
invoice issuance, or expense approval — all of which feel adjacent to daily reports.

Products that share a database become coupled and difficult to evolve independently.
Shared database patterns in the NeNe ecosystem have been explicitly rejected in favor
of HTTP reference links between products.

## Decision

NeNe Field is a **standalone product** with its own database. It does not share a
database with any sibling product.

Integration with sibling products is via **HTTP reference links only**:
- A Report may carry an optional `invoice_work_order_id` that references a
  `nene-invoice` work order via HTTP (no JOIN, no foreign key across products).
- A Report may carry an optional `records_entity_id` for linking to a `nene-records` entity.

NeNe Field must never:
- Issue quotes or invoices — that is `nene-invoice`
- Reconcile bank deposits or send dunning notices — that is `nene-clear`
- Archive received documents as SSOT — that is `nene-vault`
- Normalize bank CSV — that is `nene-profile`
- Provide payroll calculation or statutory labor management records

## Consequences

- Clear product boundaries with no accidental coupling
- Each product can evolve, deploy, and scale independently
- Integration requires HTTP calls; data duplication for display is expected and acceptable

## Related

- Issue: `#1`
- See also: `docs/explanation/scope-boundary.md`
- See also: `docs/integrations/sibling-products.md`
