<?php

declare(strict_types=1);

namespace NeneField\Tests\Http;

use NeneField\Http\RuntimeContainerFactory;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Boots the real NENE2 runtime and dispatches GET /health end-to-end.
 *
 * `/health` is auto-provided by Nene2\Http\RuntimeApplicationFactory; with no
 * registered health checks it reports overall status "ok".
 */
final class HealthTest extends TestCase
{
    public function test_health_endpoint_returns_ok(): void
    {
        $container = (new RuntimeContainerFactory(dirname(__DIR__, 2)))->create();

        $application = $container->get(RequestHandlerInterface::class);
        self::assertInstanceOf(RequestHandlerInterface::class, $application);

        $request = (new Psr17Factory())->createServerRequest('GET', '/health');
        $response = $application->handle($request);

        self::assertSame(200, $response->getStatusCode());

        /** @var array<string, mixed> $body */
        $body = (array) json_decode((string) $response->getBody(), true);
        self::assertArrayHasKey('status', $body);
        self::assertSame('ok', $body['status']);

        // The database connectivity check runs against the test SQLite (:memory:).
        self::assertArrayHasKey('checks', $body);
        self::assertIsArray($body['checks']);
        self::assertSame('ok', $body['checks']['database'] ?? null);
    }
}
