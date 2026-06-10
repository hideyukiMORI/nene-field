# NENE2 Compliance

Rules for staying compliant with the NENE2 framework conventions inherited by NeNe Field.

## Layering (binding)

```
HTTP Handler
  ↓ parses request, calls UseCase
UseCase
  ↓ business logic; calls RepositoryInterface
RepositoryInterface (domain interface)
  ↓ implemented by
PdoXxxRepository (infrastructure)
```

- Handlers are thin: parse input → call use case → return response.
- Use cases contain all business rules and authorization checks.
- Repositories are the only place where SQL lives.
- No cross-layer imports (e.g., no SQL in UseCase, no HTTP in Repository).

## Reuse framework objects (do not reinvent)

| Need | Use from NENE2 |
| --- | --- |
| JSON response | `JsonResponseFactory` |
| HTTP routing | `Router` |
| Pagination | `PaginationQuery` |
| Auth middleware | `BearerTokenMiddleware` |
| DB queries | `DatabaseQueryExecutorInterface` |
| Validation errors | `ValidationError` / `ValidationException` |
| Problem Details | `ProblemDetailsResponseFactory` |
| Audit | Pattern from NENE2 audit guide |

## Multi-tenancy rule

Every handler that touches tenanted data must:
1. Extract `organization_id` from JWT.
2. Pass it to the use case.
3. Use case passes it to the repository.
4. Repository includes it in every WHERE clause.

No exceptions. Violating this rule creates data leakage across tenants.

## Validation layers

```
Middleware  — request size, content-type, auth
Handler     — path/query/body parsing, format validation, DTO creation
UseCase     — business rules, authorization, state checks
```

Never put business rules in middleware. Never put HTTP concerns in use cases.
