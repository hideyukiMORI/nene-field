<?php

declare(strict_types=1);

namespace NeneField\Http;

use LogicException;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Http\ResponseEmitter;
use Nene2\Http\RuntimeApplicationFactory;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Wires the NeNe Field HTTP runtime.
 *
 * This is the minimal scaffold wiring: it assembles {@see RuntimeApplicationFactory}
 * directly (so the example Note/Tag routes are NOT mounted) and lets the framework
 * auto-provide `GET /health`. Domain service providers, the database/config stack,
 * and the tenant-resolution + auth middleware (multi-tenancy.md / ADR 0013) are
 * registered here as the domain endpoints land.
 */
final readonly class RuntimeServiceProvider implements ServiceProviderInterface
{
    public const PROJECT_ROOT = 'nene_field.project_root';

    /** Problem Details `type` namespace (docs/terms.md §7). */
    private const PROBLEM_DETAILS_BASE_URL = 'https://nene-field.dev/problems/';

    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                Psr17Factory::class,
                static fn (ContainerInterface $container): Psr17Factory => new Psr17Factory(),
            )
            ->set(
                RuntimeApplicationFactory::class,
                static function (ContainerInterface $container): RuntimeApplicationFactory {
                    $psr17 = $container->get(Psr17Factory::class);

                    if (!$psr17 instanceof Psr17Factory) {
                        throw new LogicException('PSR-17 factory service is invalid.');
                    }

                    return new RuntimeApplicationFactory(
                        responseFactory: $psr17,
                        streamFactory: $psr17,
                        debug: self::isDebug(),
                        problemDetailsBaseUrl: self::PROBLEM_DETAILS_BASE_URL,
                    );
                },
            )
            ->set(
                RequestHandlerInterface::class,
                static function (ContainerInterface $container): RequestHandlerInterface {
                    $factory = $container->get(RuntimeApplicationFactory::class);

                    if (!$factory instanceof RuntimeApplicationFactory) {
                        throw new LogicException('Runtime application factory service is invalid.');
                    }

                    return $factory->create();
                },
            )
            ->set(
                ResponseEmitter::class,
                static fn (ContainerInterface $container): ResponseEmitter => new ResponseEmitter(),
            );
    }

    /** Debug output is opt-in via APP_DEBUG; never enabled in production. */
    private static function isDebug(): bool
    {
        $value = $_ENV['APP_DEBUG'] ?? getenv('APP_DEBUG');

        return is_string($value) && in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
    }
}
