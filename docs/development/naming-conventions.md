# Naming Conventions

All identifiers must match `docs/terms.md`. This document provides context and examples.

## PHP Classes

| Pattern | Example |
| --- | --- |
| `XxxHandler` | `SubmitReportHandler`, `ApproveReportHandler` |
| `XxxUseCase` | `SubmitReportUseCase`, `ListReportsUseCase` |
| `XxxRepositoryInterface` | `ReportRepositoryInterface` |
| `PdoXxxRepository` | `PdoReportRepository` |
| `XxxServiceProvider` | `ReportServiceProvider` |

**Forbidden suffixes:** `Controller`, `Service`, `Manager`, `Repo`

## DB Tables and Columns

- Table names: plural snake_case (`reports`, `report_templates`, `report_attachments`)
- Column names: snake_case (`organization_id`, `work_date`, `ai_summary`)
- Forbidden: camelCase columns, `org_id` shorthand

## JSON API Fields

- Always snake_case (`report_id`, `work_date`, `submitted_at`)
- Forbidden: camelCase (`reportId`, `workDate`)

## URL Paths

- Plural nouns for collections (`/reports`, `/templates`, `/users`)
- Hyphenated segments (`/audit-events`)
- Forbidden: camelCase paths, verb-first paths (use POST + noun instead)
