<?php

declare(strict_types=1);

namespace NeneField\Organization;

use Closure;
use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\ClockInterface;
use Nene2\Http\JsonResponseFactory;
use NeneField\AuditEvent\AuditServiceProvider;
use Psr\Container\ContainerInterface;

final readonly class OrganizationServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                OrganizationRepositoryInterface::class,
                static fn (ContainerInterface $c): OrganizationRepositoryInterface => new PdoOrganizationRepository(self::executor($c)),
            )
            ->set(
                ListOrganizationsUseCaseInterface::class,
                static fn (ContainerInterface $c): ListOrganizationsUseCaseInterface => new ListOrganizationsUseCase(self::organizations($c)),
            )
            ->set(
                GetOrganizationUseCaseInterface::class,
                static fn (ContainerInterface $c): GetOrganizationUseCaseInterface => new GetOrganizationUseCase(self::organizations($c)),
            )
            ->set(
                CreateOrganizationUseCaseInterface::class,
                static fn (ContainerInterface $c): CreateOrganizationUseCaseInterface
                    => new CreateOrganizationUseCase(self::organizations($c), self::tx($c), self::auditFactory($c), self::clock($c)),
            )
            ->set(
                UpdateOrganizationUseCaseInterface::class,
                static fn (ContainerInterface $c): UpdateOrganizationUseCaseInterface
                    => new UpdateOrganizationUseCase(self::organizations($c), self::tx($c), self::auditFactory($c), self::clock($c)),
            )
            ->set(
                ListOrganizationsHandler::class,
                static function (ContainerInterface $c): ListOrganizationsHandler {
                    $useCase = $c->get(ListOrganizationsUseCaseInterface::class);

                    if (!$useCase instanceof ListOrganizationsUseCaseInterface) {
                        throw new LogicException('List organizations use case service is invalid.');
                    }

                    return new ListOrganizationsHandler($useCase, self::json($c), self::problemDetails($c));
                },
            )
            ->set(
                GetOrganizationHandler::class,
                static function (ContainerInterface $c): GetOrganizationHandler {
                    $useCase = $c->get(GetOrganizationUseCaseInterface::class);

                    if (!$useCase instanceof GetOrganizationUseCaseInterface) {
                        throw new LogicException('Get organization use case service is invalid.');
                    }

                    return new GetOrganizationHandler($useCase, self::json($c), self::problemDetails($c));
                },
            )
            ->set(
                CreateOrganizationHandler::class,
                static function (ContainerInterface $c): CreateOrganizationHandler {
                    $useCase = $c->get(CreateOrganizationUseCaseInterface::class);

                    if (!$useCase instanceof CreateOrganizationUseCaseInterface) {
                        throw new LogicException('Create organization use case service is invalid.');
                    }

                    return new CreateOrganizationHandler($useCase, self::json($c), self::problemDetails($c));
                },
            )
            ->set(
                UpdateOrganizationHandler::class,
                static function (ContainerInterface $c): UpdateOrganizationHandler {
                    $useCase = $c->get(UpdateOrganizationUseCaseInterface::class);

                    if (!$useCase instanceof UpdateOrganizationUseCaseInterface) {
                        throw new LogicException('Update organization use case service is invalid.');
                    }

                    return new UpdateOrganizationHandler($useCase, self::json($c), self::problemDetails($c));
                },
            )
            ->set(
                OrganizationRouteRegistrar::class,
                static function (ContainerInterface $c): OrganizationRouteRegistrar {
                    $list = $c->get(ListOrganizationsHandler::class);
                    $create = $c->get(CreateOrganizationHandler::class);
                    $get = $c->get(GetOrganizationHandler::class);
                    $update = $c->get(UpdateOrganizationHandler::class);

                    if (
                        !$list instanceof ListOrganizationsHandler
                        || !$create instanceof CreateOrganizationHandler
                        || !$get instanceof GetOrganizationHandler
                        || !$update instanceof UpdateOrganizationHandler
                    ) {
                        throw new LogicException('Organization handler services are invalid.');
                    }

                    return new OrganizationRouteRegistrar($list, $create, $get, $update);
                },
            );
    }

    private static function organizations(ContainerInterface $c): OrganizationRepositoryInterface
    {
        $organizations = $c->get(OrganizationRepositoryInterface::class);

        if (!$organizations instanceof OrganizationRepositoryInterface) {
            throw new LogicException('Organization repository service is invalid.');
        }

        return $organizations;
    }

    private static function executor(ContainerInterface $c): DatabaseQueryExecutorInterface
    {
        $executor = $c->get(DatabaseQueryExecutorInterface::class);

        if (!$executor instanceof DatabaseQueryExecutorInterface) {
            throw new LogicException('Database query executor service is invalid.');
        }

        return $executor;
    }

    private static function tx(ContainerInterface $c): DatabaseTransactionManagerInterface
    {
        $tx = $c->get(DatabaseTransactionManagerInterface::class);

        if (!$tx instanceof DatabaseTransactionManagerInterface) {
            throw new LogicException('Transaction manager service is invalid.');
        }

        return $tx;
    }

    /**
     * @return Closure(DatabaseQueryExecutorInterface): \NeneField\AuditEvent\AuditRecorderInterface
     */
    private static function auditFactory(ContainerInterface $c): Closure
    {
        $factory = $c->get(AuditServiceProvider::RECORDER_FACTORY);

        if (!$factory instanceof Closure) {
            throw new LogicException('Audit recorder factory service is invalid.');
        }

        return $factory;
    }

    private static function clock(ContainerInterface $c): ClockInterface
    {
        $clock = $c->get(ClockInterface::class);

        if (!$clock instanceof ClockInterface) {
            throw new LogicException('Clock service is invalid.');
        }

        return $clock;
    }

    private static function json(ContainerInterface $c): JsonResponseFactory
    {
        $json = $c->get(JsonResponseFactory::class);

        if (!$json instanceof JsonResponseFactory) {
            throw new LogicException('JSON response factory service is invalid.');
        }

        return $json;
    }

    private static function problemDetails(ContainerInterface $c): ProblemDetailsResponseFactory
    {
        $problemDetails = $c->get(ProblemDetailsResponseFactory::class);

        if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
            throw new LogicException('Problem details response factory service is invalid.');
        }

        return $problemDetails;
    }
}
