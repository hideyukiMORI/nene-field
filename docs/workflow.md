# Workflow

NeNe Field uses GitHub Issues for work tracking and local Markdown for project memory.
This workflow inherits [NENE2 `docs/workflow.md`](https://github.com/hideyukiMORI/NENE2/blob/main/docs/workflow.md).

See also: `docs/inheritance-from-nene2.md`.

## Standard Flow

1. Create or reuse a focused GitHub Issue.
2. Confirm context in `docs/roadmap.md`, `docs/milestones/`, and `docs/todo/current.md`.
3. Create a branch from `main` named like `type/issue-number-summary`.
4. Implement the smallest useful change.
5. Update docs, roadmap, milestone, or TODO files when the decision or state changes.
6. Review the relevant self-review checklist in `docs/review/`.
7. Run the narrowest meaningful verification available.
8. Commit with Conventional Commits and include the Issue number.
9. Push the branch and create a PR linked to the Issue.
10. Merge after review and checks.
11. Return local `main` to the merged, clean state.

## Branch Names

Use Conventional Commit style as the prefix:

- `docs/1-governance-foundation`
- `feat/4-nene2-runtime-scaffold`
- `feat/8-report-submission-api`
- `feat/12-approval-workflow`
- `fix/20-csv-export-date-filter`
- `test/15-report-audit-log`

## PR Requirements

Every PR should include:

- purpose
- change summary
- verification results
- self-review checklist used, when applicable
- related Issue, preferably `Closes #number`
- remaining risks or follow-up work

## Closing Multiple Issues

When one PR closes multiple issues, repeat the keyword for each:

```
Closes #3, Closes #5, Closes #7
```

A single `Closes #3, #5, #7` only closes the first; the rest stay open.

## Commit Message Format

```
<type>(<scope>): <Japanese description> (#<issue>)

[optional body — reason, trade-offs, follow-up]
```

- `type` and `scope`: English
- description and body: Japanese
- Breaking API changes: add `!` or `BREAKING CHANGE:` footer
