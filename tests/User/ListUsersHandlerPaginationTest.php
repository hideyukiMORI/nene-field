<?php

declare(strict_types=1);

namespace NeneField\Tests\User;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Validation\ValidationException;
use NeneField\User\ListUsersHandler;
use NeneField\User\ListUsersOutput;
use NeneField\User\ListUsersUseCaseInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Pagination boundary handling for the list endpoints. Parsing/validation is now
 * delegated to {@see \Nene2\Http\PaginationQueryParser} (limit ∈ [1, 100] default
 * 20; offset ≥ 0 default 0): in-range values pass through unchanged and
 * out-of-range values raise a ValidationException (→ HTTP 422 via the error
 * middleware). The rule is identical across the list handlers, so
 * `ListUsersHandler` covers it representatively.
 */
final class ListUsersHandlerPaginationTest extends TestCase
{
    #[DataProvider('validLimits')]
    public function test_valid_limit_passes_through(?string $raw, int $expected): void
    {
        $useCase = new CapturingListUsersUseCase();
        $query = $raw === null ? [] : ['limit' => $raw];

        $this->dispatch($useCase, $query);

        self::assertSame($expected, $useCase->limit);
    }

    /**
     * @return iterable<string, array{?string, int}>
     */
    public static function validLimits(): iterable
    {
        yield 'absent → default' => [null, 20];
        yield 'one → 1' => ['1', 1];
        yield 'mid → as-is' => ['50', 50];
        yield 'max → 100' => ['100', 100];
    }

    #[DataProvider('invalidLimits')]
    public function test_out_of_range_limit_is_rejected(string $raw): void
    {
        $useCase = new CapturingListUsersUseCase();

        $this->expectException(ValidationException::class);
        $this->dispatch($useCase, ['limit' => $raw]);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidLimits(): iterable
    {
        yield 'zero → 422' => ['0'];
        yield 'negative → 422' => ['-5'];
        yield 'over → 422' => ['101'];
        yield 'far over → 422' => ['100000'];
    }

    #[DataProvider('validOffsets')]
    public function test_valid_offset_passes_through(?string $raw, int $expected): void
    {
        $useCase = new CapturingListUsersUseCase();
        $query = $raw === null ? [] : ['offset' => $raw];

        $this->dispatch($useCase, $query);

        self::assertSame($expected, $useCase->offset);
    }

    /**
     * @return iterable<string, array{?string, int}>
     */
    public static function validOffsets(): iterable
    {
        yield 'absent → 0' => [null, 0];
        yield 'zero → 0' => ['0', 0];
        yield 'positive → as-is' => ['40', 40];
    }

    public function test_negative_offset_is_rejected(): void
    {
        $useCase = new CapturingListUsersUseCase();

        $this->expectException(ValidationException::class);
        $this->dispatch($useCase, ['offset' => '-1']);
    }

    /**
     * @param array<string, string> $query
     */
    private function dispatch(CapturingListUsersUseCase $useCase, array $query): void
    {
        $psr17 = new Psr17Factory();
        $handler = new ListUsersHandler(
            $useCase,
            new JsonResponseFactory($psr17, $psr17),
            new ProblemDetailsResponseFactory($psr17, $psr17, 'https://nene-field.dev/problems/'),
        );

        $request = $psr17->createServerRequest('GET', '/users')
            ->withQueryParams($query)
            ->withAttribute('nene2.auth.claims', ['sub' => 'admin-1', 'role' => 'admin', 'org' => 'org-1']);

        $response = $handler->handle($request);
        self::assertSame(200, $response->getStatusCode());
    }
}

/**
 * Records the limit/offset the handler computed so the parsing can be asserted.
 */
final class CapturingListUsersUseCase implements ListUsersUseCaseInterface
{
    public int $limit = -1;
    public int $offset = -1;

    public function execute(string $organizationId, int $limit, int $offset): ListUsersOutput
    {
        $this->limit = $limit;
        $this->offset = $offset;

        return new ListUsersOutput(items: [], total: 0, limit: $limit, offset: $offset);
    }
}
