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

Exact symbols (verify against `vendor/hideyukimori/nene2/`):

| Need | Use from NENE2 |
| --- | --- |
| JSON response | `Nene2\Http\JsonResponseFactory` |
| Parse JSON body | `Nene2\Http\JsonRequestBodyParser` |
| HTTP routing / path params | `Nene2\Routing\Router` (params via `Router::PARAMETERS_ATTRIBUTE`) |
| Pagination | `Nene2\Http\PaginationQueryParser` + `Nene2\Http\PaginationResponse` |
| Auth middleware | `Nene2\Auth\BearerTokenMiddleware` (+ `TokenIssuerInterface`) |
| DB queries | `Nene2\Database\DatabaseQueryExecutorInterface` |
| Transactions | `Nene2\Database\DatabaseTransactionManagerInterface` |
| DB constraint errors | `Nene2\Database\DatabaseConstraintException` |
| Validation errors | `Nene2\Validation\ValidationError` / `ValidationException` |
| Problem Details | `Nene2\Error\ProblemDetailsResponseFactory` |
| Domain exception mapping | `Nene2\Error\DomainExceptionHandlerInterface` |
| Time ("now") | `Nene2\Http\ClockInterface` (`UtcClock`) |
| Per-request context | `Nene2\Http\RequestScopedHolder` |
| Typed config | `Nene2\Config\AppConfig` |
| DI registration | `Nene2\DependencyInjection\ContainerBuilder` + `ServiceProviderInterface` |

Detailed rules: [`backend-standards.md`](./backend-standards.md).

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
