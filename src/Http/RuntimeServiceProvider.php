<?php

declare(strict_types=1);

namespace NeneField\Http;

use LogicException;
use Nene2\Auth\BearerTokenMiddleware;
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
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\RequestScopedHolder;
use Nene2\Http\ResponseEmitter;
use Nene2\Http\RuntimeApplicationFactory;
use Nene2\Http\UtcClock;
use Nene2\Log\RequestIdHolder;
use NeneField\Attachment\AttachmentRouteRegistrar;
use NeneField\Attachment\AttachmentServiceProvider;
use NeneField\AuditEvent\AuditServiceProvider;
use NeneField\Auth\AuthRouteRegistrar;
use NeneField\Auth\AuthServiceProvider;
use NeneField\Auth\OrgGuardMiddleware;
use NeneField\Export\ExportRouteRegistrar;
use NeneField\Export\ExportServiceProvider;
use NeneField\Organization\OrganizationRepositoryInterface;
use NeneField\Organization\OrganizationRouteRegistrar;
use NeneField\Organization\OrganizationServiceProvider;
use NeneField\Organization\Resolution\EnvResolutionStrategy;
use NeneField\Organization\Resolution\OrgResolutionStrategyInterface;
use NeneField\Organization\Resolution\OrgResolverMiddleware;
use NeneField\Organization\Resolution\PathPrefixResolutionStrategy;
use NeneField\Organization\Resolution\SubdomainResolutionStrategy;
use NeneField\Report\ReportRouteRegistrar;
use NeneField\Report\ReportServiceProvider;
use NeneField\Template\TemplateRouteRegistrar;
use NeneField\Template\TemplateServiceProvider;
use NeneField\User\UserRouteRegistrar;
use NeneField\User\UserServiceProvider;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Wires the NeNe Field HTTP runtime: config, database adapters, the shared
 * request-scoped org-id holder, the tenant-resolution + auth + org-guard
 * middleware pipeline, and the application request handler.
 *
 * `GET /health` (with a database check) is provided by RuntimeApplicationFactory;
 * the example Note/Tag routes are NOT mounted. Domain route registrars are
 * appended to `routeRegistrars` as each domain lands.
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
        $builder->addProvider(new UserServiceProvider());
        $builder->addProvider(new AuthServiceProvider());
        $builder->addProvider(new AuditServiceProvider());
        $builder->addProvider(new ReportServiceProvider());
        $builder->addProvider(new TemplateServiceProvider());
        $builder->addProvider(new AttachmentServiceProvider());
        $builder->addProvider(new ExportServiceProvider());

        $builder
            ->set(
                Psr17Factory::class,
                static fn (ContainerInterface $container): Psr17Factory => new Psr17Factory(),
            )
            ->set(
                JsonResponseFactory::class,
                static function (ContainerInterface $container): JsonResponseFactory {
                    $psr17 = self::psr17($container);

                    return new JsonResponseFactory($psr17, $psr17);
                },
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
                static fn (ContainerInterface $container): DatabaseQueryExecutorInterface
                    => new PdoDatabaseQueryExecutor(self::connectionFactory($container)),
            )
            ->set(
                DatabaseTransactionManagerInterface::class,
                static fn (ContainerInterface $container): DatabaseTransactionManagerInterface
                    => new PdoDatabaseTransactionManager(self::connectionFactory($container)),
            )
            ->set(
                ClockInterface::class,
                static fn (ContainerInterface $container): ClockInterface => new UtcClock(),
            )
            ->set(
                ProblemDetailsResponseFactory::class,
                static function (ContainerInterface $container): ProblemDetailsResponseFactory {
                    $psr17 = self::psr17($container);

                    return new ProblemDetailsResponseFactory($psr17, $psr17, self::PROBLEM_DETAILS_BASE_URL);
                },
            )
            ->set(
                self::ORG_ID_HOLDER,
                static fn (ContainerInterface $container): RequestScopedHolder => new RequestScopedHolder(),
            )
            ->set(
                RequestIdHolder::class,
                static fn (ContainerInterface $container): RequestIdHolder => new RequestIdHolder(),
            )
            ->set(
                DatabaseHealthCheck::class,
                static fn (ContainerInterface $container): DatabaseHealthCheck
                    => new DatabaseHealthCheck(self::connectionFactory($container)),
            )
            ->set(
                OrgResolverMiddleware::class,
                static function (ContainerInterface $container): OrgResolverMiddleware {
                    $holder = $container->get(self::ORG_ID_HOLDER);

                    if (!$holder instanceof RequestScopedHolder) {
                        throw new LogicException('Org id holder service is invalid.');
                    }

                    $repository = $container->get(OrganizationRepositoryInterface::class);

                    if (!$repository instanceof OrganizationRepositoryInterface) {
                        throw new LogicException('Organization repository service is invalid.');
                    }

                    /** @var RequestScopedHolder<string> $holder */
                    return new OrgResolverMiddleware(
                        $holder,
                        $repository,
                        self::problemDetails($container),
                        self::resolutionStrategy(),
                    );
                },
            )
            ->set(
                RuntimeApplicationFactory::class,
                static function (ContainerInterface $container): RuntimeApplicationFactory {
                    $psr17 = self::psr17($container);

                    $config = $container->get(AppConfig::class);

                    if (!$config instanceof AppConfig) {
                        throw new LogicException('Application config service is invalid.');
                    }

                    $databaseHealthCheck = $container->get(DatabaseHealthCheck::class);

                    if (!$databaseHealthCheck instanceof DatabaseHealthCheck) {
                        throw new LogicException('Database health check service is invalid.');
                    }

                    $orgResolver = $container->get(OrgResolverMiddleware::class);
                    $bearer = $container->get(BearerTokenMiddleware::class);
                    $orgGuard = $container->get(OrgGuardMiddleware::class);
                    $authRoutes = $container->get(AuthRouteRegistrar::class);
                    $reportRoutes = $container->get(ReportRouteRegistrar::class);
                    $userRoutes = $container->get(UserRouteRegistrar::class);
                    $orgRoutes = $container->get(OrganizationRouteRegistrar::class);
                    $templateRoutes = $container->get(TemplateRouteRegistrar::class);
                    $attachmentRoutes = $container->get(AttachmentRouteRegistrar::class);
                    $exportRoutes = $container->get(ExportRouteRegistrar::class);
                    $requestIdHolder = $container->get(RequestIdHolder::class);

                    if (
                        !$orgResolver instanceof OrgResolverMiddleware
                        || !$bearer instanceof BearerTokenMiddleware
                        || !$orgGuard instanceof OrgGuardMiddleware
                        || !$authRoutes instanceof AuthRouteRegistrar
                        || !$reportRoutes instanceof ReportRouteRegistrar
                        || !$userRoutes instanceof UserRouteRegistrar
                        || !$orgRoutes instanceof OrganizationRouteRegistrar
                        || !$templateRoutes instanceof TemplateRouteRegistrar
                        || !$attachmentRoutes instanceof AttachmentRouteRegistrar
                        || !$exportRoutes instanceof ExportRouteRegistrar
                        || !$requestIdHolder instanceof RequestIdHolder
                    ) {
                        throw new LogicException('Runtime middleware/route services are invalid.');
                    }

                    return new RuntimeApplicationFactory(
                        responseFactory: $psr17,
                        streamFactory: $psr17,
                        requestIdHolder: $requestIdHolder,
                        routeRegistrars: [$authRoutes, $reportRoutes, $userRoutes, $orgRoutes, $templateRoutes, $attachmentRoutes, $exportRoutes],
                        authMiddleware: [$orgResolver, $bearer, $orgGuard],
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

    private static function resolutionStrategy(): OrgResolutionStrategyInterface
    {
        $mode = self::env('NENE_FIELD_TENANT_RESOLUTION', 'single');
        $slug = self::env('NENE_FIELD_ORG_SLUG', '');
        $baseDomain = self::env('NENE_FIELD_BASE_DOMAIN', 'localhost');

        return match ($mode) {
            'subdomain' => new SubdomainResolutionStrategy($baseDomain),
            'path' => new PathPrefixResolutionStrategy(),
            default => new EnvResolutionStrategy($slug),
        };
    }

    private static function env(string $key, string $default): string
    {
        $value = $_SERVER[$key] ?? $_ENV[$key] ?? getenv($key);

        return is_string($value) && $value !== '' ? $value : $default;
    }

    private static function psr17(ContainerInterface $container): Psr17Factory
    {
        $psr17 = $container->get(Psr17Factory::class);

        if (!$psr17 instanceof Psr17Factory) {
            throw new LogicException('PSR-17 factory service is invalid.');
        }

        return $psr17;
    }

    private static function connectionFactory(ContainerInterface $container): DatabaseConnectionFactoryInterface
    {
        $factory = $container->get(DatabaseConnectionFactoryInterface::class);

        if (!$factory instanceof DatabaseConnectionFactoryInterface) {
            throw new LogicException('Database connection factory service is invalid.');
        }

        return $factory;
    }

    private static function problemDetails(ContainerInterface $container): ProblemDetailsResponseFactory
    {
        $problemDetails = $container->get(ProblemDetailsResponseFactory::class);

        if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
            throw new LogicException('Problem details response factory service is invalid.');
        }

        return $problemDetails;
    }
}
