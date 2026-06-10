# Product Vision

> **Product name:** **NeNe Field** — see [ADR 0005](../adr/0005-product-identity-nene-field.md).

NeNe Field is a self-hosted **daily report platform** on
[NENE2](https://github.com/hideyukiMORI/NENE2). It exists so Japan SMB operators
can **eliminate paper reports and chat-based status updates** while giving managers
a fast, structured view of what happened in the field — without adopting enterprise
workflows or HR software.

## Origin

Field operations teams at small businesses (construction crews, retail branches,
beauty salons, tutoring centers) face three persistent pains:

1. **Report chaos** — reports arrive via chat, paper, and email with no consistent format.
   Collection takes time; data is lost or forgotten.
2. **Approval bottleneck** — managers are the only person who can "confirm" a report,
   but they are busy. Approvals pile up; field workers do not know if their report was seen.
3. **No usable data** — there is no structured output to connect field work to billing,
   payroll, or project management.

That is **field communication**, not invoicing, reconciliation, or document archiving.

## North Star

Field workers can:
- submit a report from a smartphone in under 3 minutes
- attach a photo as evidence
- get notified when their report is approved or returned

Managers can:
- approve 10 reports in 5 minutes from a phone
- see an AI-generated one-line summary instead of reading full text
- export one month's data as CSV in two clicks

## What we explicitly do not build

| Capability | Owner |
| --- | --- |
| Payroll calculation / statutory overtime | Dedicated payroll software |
| Quote / invoice issuance | **NeNe Invoice** |
| Bank reconciliation | **NeNe Clear** |
| Received-document archive | **NeNe Vault** |
| Bank CSV normalization | **NeNe Profile** |
| Expense reimbursement workflows | Future separate product |

## Target operators

**Primary — Japan SMB team lead / office manager (5–100 employees)**
who currently collects daily reports by LINE, email, or paper notebooks.
Wants structured records without paying for a large SaaS platform.

**Secondary — Tier B developer / system integrator**
building a custom field operations stack and needing a lightweight, self-hosted
report + approval backend.

## Primary personas

**Persona A — Field worker**
> A construction worker on a 10-person crew.
> Submits a daily progress photo + text from Android at the end of shift.
> Cannot use a PC. Needs the form to take 2 minutes max.

**Persona B — Team leader / approver**
> A retail branch manager overseeing 8 part-time staff.
> Reviews 8 daily reports each evening from an iPhone.
> Currently reads LINE messages; wants a "read + approve" button instead.

**Persona C — Business owner / admin**
> Owner of a 30-person landscaping company.
> Needs monthly CSV of crew work records to hand to a payroll processor.
> Does not want to touch a server; wants a release ZIP on shared hosting.
