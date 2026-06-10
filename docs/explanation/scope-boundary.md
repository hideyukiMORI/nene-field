# Scope Boundary

Visual reference for where NeNe Field ends and sibling products begin.

## What NeNe Field does

```
Field worker (smartphone)
  │
  ├── Submit daily report (text + photo + template)
  ├── Attach evidence photos
  └── Check own report status

Manager / Approver (smartphone or desktop)
  │
  ├── View report list (filtered)
  ├── Approve / reject with comment
  └── Export CSV

Admin
  │
  ├── Manage templates
  ├── Manage users & roles
  ├── View audit log
  └── Configure AI summary, notifications
```

## What it does NOT do

```
NeNe Field  →  hands off to  →  other products / tools

Report CSV  →  operator imports  →  payroll software (法定帳簿)
Work hours  →  operator uses  →  dedicated timesheet / attendance tool
Billable hours  →  HTTP link (optional)  →  nene-invoice work order
Expense receipts  →  not supported  →  future nene-expense or manual
Bank data  →  nene-profile / nene-clear
Vendor invoices  →  nene-vault
```

## Integration points (HTTP reference only)

| From NeNe Field | To | Link type |
| --- | --- | --- |
| `reports.invoice_work_order_id` | `nene-invoice` `/work-orders/{id}` | Optional HTTP reference |
| `reports.records_entity_id` | `nene-records` `/entities/{id}` | Optional HTTP reference |

These are **read-only references**. NeNe Field never writes to sibling product databases.
