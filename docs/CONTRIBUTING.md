# Contributing

NeNe Field is an open-source project. Contributions are welcome under the [MIT License](../LICENSE).

## Before you start

- Read [`AGENTS.md`](../AGENTS.md) for the full operating rules.
- Read [`docs/explanation/scope-contract.md`](./explanation/scope-contract.md) to understand what is in and out of scope.
- Read [`docs/terms.md`](./terms.md) for canonical identifier spellings — violations block merge.
- Check [`docs/todo/current.md`](./todo/current.md) and open Issues before starting new work.

## Issue-driven workflow

All code, documentation, and configuration changes require a GitHub Issue.
Do not commit directly to `main`. See [`docs/workflow.md`](./workflow.md).

## Coding standards

See [`docs/development/coding-standards.md`](./development/coding-standards.md).

## Commit messages

See [`docs/development/commit-conventions.md`](./development/commit-conventions.md).

## Pull requests

- Link to an Issue with `Closes #number`.
- Include the self-review checklist name from `docs/review/`.
- Ensure `composer check` and `npm run check --prefix frontend` are green before requesting review.

## Out of scope

Do not add:
- Payroll calculation or statutory labor management
- Invoice issuance — that is `nene-invoice`
- Bank reconciliation — that is `nene-clear`
- Received-document archiving as SSOT — that is `nene-vault`

See [`docs/explanation/scope-boundary.md`](./explanation/scope-boundary.md).
