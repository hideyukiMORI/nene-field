<?php

declare(strict_types=1);

namespace NeneField\Http;

use LogicException;
use Nene2\Config\AppConfig;
use Nene2\Config\ConfigLoader;
use Nene2\Database\DatabaseConnectionFactoryInterface;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use Nene2\Database\PdoDatabaseTransactionManager;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\ClockInterface;
use Nene2\Http\RequestScopedHolder;
use Nene2\Http\ResponseEmitter;
use Nene2\Http\RuntimeApplicationFactory;
use Nene2\Http\UtcClock;
use NeneField\Organization\OrganizationServiceProvider;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Wires the NeNe Field HTTP runtime: config, database adapters, the shared
 * request-scoped org-id holder, and the application request handler.
 *
 * `GET /health` (with a database connectivity check) is provided by
 * {@see RuntimeApplicationFactory}. The example Note/Tag routes are intentionally
 * NOT mounted. The tenant-resolution + auth middleware
 * ({@see \NeneField\Organization\Resolution\OrgResolverMiddleware}) is registered
 * here but inserted into the pipeline together with authentication (next issue).
 */
final readonly class RuntimeServiceProvider implements ServiceProviderInterface
{
    public const PROJECT_ROOT = 'nene_field.project_root';

    /** Shared per-request holder for the resolved organization UUID. */
    public const ORG_ID_HOLDER = 'nene_field.org_id_holder';

    /** Problem Details `type` namespace (docs/terms.md §7). */
    public const PROBLEM_DETAILS_BASE_URL = 'https://nene-field.dev/problems/';

    public function register(ContainerBuilder $builder): void
    {
        $builder->addProvider(new OrganizationServiceProvider());

        $builder
            ->set(
                Psr17Factory::class,
                static fn (ContainerInterface $container): Psr17Factory => new Psr17Factory(),
            )
            ->set(
                AppConfig::class,
                static function (ContainerInterface $container): AppConfig {
                    $projectRoot = $container->get(self::PROJECT_ROOT);

                    if (!is_string($projectRoot) || $projectRoot === '') {
                        throw new LogicException('Project root service is invalid.');
                    }

                    return (new ConfigLoader($projectRoot))->load();
                },
            )
            ->set(
                DatabaseConnectionFactoryInterface::class,
                static function (ContainerInterface $container): DatabaseConnectionFactoryInterface {
                    $config = $container->get(AppConfig::class);

                    if (!$config instanceof AppConfig) {
                        throw new LogicException('Application config service is invalid.');
                    }

                    return new PdoConnectionFactory($config->database);
                },
            )
            ->set(
                DatabaseQueryExecutorInterface::class,
                static function (ContainerInterface $container): DatabaseQueryExecutorInterface {
                    $factory = $container->get(DatabaseConnectionFactoryInterface::class);

                    if (!$factory instanceof DatabaseConnectionFactoryInterface) {
                        throw new LogicException('Database connection factory service is invalid.');
                    }

                    return new PdoDatabaseQueryExecutor($factory);
                },
            )
            ->set(
                DatabaseTransactionManagerInterface::class,
                static function (ContainerInterface $container): DatabaseTransactionManagerInterface {
                    $factory = $container->get(DatabaseConnectionFactoryInterface::class);

                    if (!$factory instanceof DatabaseConnectionFactoryInterface) {
                        throw new LogicException('Database connection factory service is invalid.');
                    }

                    return new PdoDatabaseTransactionManager($factory);
                },
            )
            ->set(
                ClockInterface::class,
                static fn (ContainerInterface $container): ClockInterface => new UtcClock(),
            )
            ->set(
                ProblemDetailsResponseFactory::class,
                static function (ContainerInterface $container): ProblemDetailsResponseFactory {
                    $psr17 = $container->get(Psr17Factory::class);

                    if (!$psr17 instanceof Psr17Factory) {
                        throw new LogicException('PSR-17 factory service is invalid.');
                    }

                    return new ProblemDetailsResponseFactory($psr17, $psr17, self::PROBLEM_DETAILS_BASE_URL);
                },
            )
            ->set(
                self::ORG_ID_HOLDER,
                static fn (ContainerInterface $container): RequestScopedHolder => new RequestScopedHolder(),
            )
            ->set(
                DatabaseHealthCheck::class,
                static function (ContainerInterface $container): DatabaseHealthCheck {
                    $factory = $container->get(DatabaseConnectionFactoryInterface::class);

                    if (!$factory instanceof DatabaseConnectionFactoryInterface) {
                        throw new LogicException('Database connection factory service is invalid.');
                    }

                    return new DatabaseHealthCheck($factory);
                },
            )
            ->set(
                RuntimeApplicationFactory::class,
                static function (ContainerInterface $container): RuntimeApplicationFactory {
                    $psr17 = $container->get(Psr17Factory::class);

                    if (!$psr17 instanceof Psr17Factory) {
                        throw new LogicException('PSR-17 factory service is invalid.');
                    }

                    $config = $container->get(AppConfig::class);

                    if (!$config instanceof AppConfig) {
                        throw new LogicException('Application config service is invalid.');
                    }

                    $databaseHealthCheck = $container->get(DatabaseHealthCheck::class);

                    if (!$databaseHealthCheck instanceof DatabaseHealthCheck) {
                        throw new LogicException('Database health check service is invalid.');
                    }

                    return new RuntimeApplicationFactory(
                        responseFactory: $psr17,
                        streamFactory: $psr17,
                        healthChecks: [$databaseHealthCheck],
                        debug: $config->debug,
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
}
