# Commit Conventions

NeNe Field follows Conventional Commits inherited from NENE2.

## Format

```
<type>(<scope>): <Japanese description> (#<issue>)

[optional body — reason, trade-offs, follow-up]
```

- `type` and `scope`: **English**
- description and body: **Japanese**
- Include `(#issue)` in the subject line

## Types

| type | Use |
| --- | --- |
| `feat` | New feature |
| `fix` | Bug fix |
| `docs` | Documentation only |
| `refactor` | Code change without behavior change |
| `test` | Test additions or changes |
| `build` | Dependency or build config |
| `ci` | CI configuration |
| `chore` | Maintenance |

## Examples

```
feat(reports): 日報提出APIを実装 (#8)
fix(approval): 承認済み日報の編集を禁止するバリデーション追加 (#15)
docs(openapi): /reports エンドポイントのレスポンス例を追加 (#10)
```
