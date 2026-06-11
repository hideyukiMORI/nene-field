<?php

declare(strict_types=1);

namespace NeneField\User;

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

final readonly class UserServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                UserRepositoryInterface::class,
                static fn (ContainerInterface $c): UserRepositoryInterface => new PdoUserRepository(self::executor($c)),
            )
            ->set(
                ListUsersUseCaseInterface::class,
                static fn (ContainerInterface $c): ListUsersUseCaseInterface => new ListUsersUseCase(self::users($c)),
            )
            ->set(
                GetUserUseCaseInterface::class,
                static fn (ContainerInterface $c): GetUserUseCaseInterface => new GetUserUseCase(self::users($c)),
            )
            ->set(
                CreateUserUseCaseInterface::class,
                static fn (ContainerInterface $c): CreateUserUseCaseInterface
                    => new CreateUserUseCase(self::users($c), self::tx($c), self::auditFactory($c), self::clock($c)),
            )
            ->set(
                UpdateUserUseCaseInterface::class,
                static fn (ContainerInterface $c): UpdateUserUseCaseInterface
                    => new UpdateUserUseCase(self::users($c), self::tx($c), self::auditFactory($c), self::clock($c)),
            )
            ->set(
                DeleteUserUseCaseInterface::class,
                static fn (ContainerInterface $c): DeleteUserUseCaseInterface
                    => new DeleteUserUseCase(self::users($c), self::tx($c), self::auditFactory($c)),
            )
            ->set(
                ListUsersHandler::class,
                static function (ContainerInterface $c): ListUsersHandler {
                    $useCase = $c->get(ListUsersUseCaseInterface::class);

                    if (!$useCase instanceof ListUsersUseCaseInterface) {
                        throw new LogicException('List users use case service is invalid.');
                    }

                    return new ListUsersHandler($useCase, self::json($c), self::problemDetails($c));
                },
            )
            ->set(
                GetUserHandler::class,
                static function (ContainerInterface $c): GetUserHandler {
                    $useCase = $c->get(GetUserUseCaseInterface::class);

                    if (!$useCase instanceof GetUserUseCaseInterface) {
                        throw new LogicException('Get user use case service is invalid.');
                    }

                    return new GetUserHandler($useCase, self::json($c), self::problemDetails($c));
                },
            )
            ->set(
                CreateUserHandler::class,
                static function (ContainerInterface $c): CreateUserHandler {
                    $useCase = $c->get(CreateUserUseCaseInterface::class);

                    if (!$useCase instanceof CreateUserUseCaseInterface) {
                        throw new LogicException('Create user use case service is invalid.');
                    }

                    return new CreateUserHandler($useCase, self::json($c), self::problemDetails($c));
                },
            )
            ->set(
                UpdateUserHandler::class,
                static function (ContainerInterface $c): UpdateUserHandler {
                    $useCase = $c->get(UpdateUserUseCaseInterface::class);

                    if (!$useCase instanceof UpdateUserUseCaseInterface) {
                        throw new LogicException('Update user use case service is invalid.');
                    }

                    return new UpdateUserHandler($useCase, self::json($c), self::problemDetails($c));
                },
            )
            ->set(
                DeleteUserHandler::class,
                static function (ContainerInterface $c): DeleteUserHandler {
                    $useCase = $c->get(DeleteUserUseCaseInterface::class);

                    if (!$useCase instanceof DeleteUserUseCaseInterface) {
                        throw new LogicException('Delete user use case service is invalid.');
                    }

                    return new DeleteUserHandler($useCase, self::json($c), self::problemDetails($c));
                },
            )
            ->set(
                UserRouteRegistrar::class,
                static function (ContainerInterface $c): UserRouteRegistrar {
                    $list = $c->get(ListUsersHandler::class);
                    $create = $c->get(CreateUserHandler::class);
                    $get = $c->get(GetUserHandler::class);
                    $update = $c->get(UpdateUserHandler::class);
                    $delete = $c->get(DeleteUserHandler::class);

                    if (
                        !$list instanceof ListUsersHandler
                        || !$create instanceof CreateUserHandler
                        || !$get instanceof GetUserHandler
                        || !$update instanceof UpdateUserHandler
                        || !$delete instanceof DeleteUserHandler
                    ) {
                        throw new LogicException('User handler services are invalid.');
                    }

                    return new UserRouteRegistrar($list, $create, $get, $update, $delete);
                },
            );
    }

    private static function users(ContainerInterface $c): UserRepositoryInterface
    {
        $users = $c->get(UserRepositoryInterface::class);

        if (!$users instanceof UserRepositoryInterface) {
            throw new LogicException('User repository service is invalid.');
        }

        return $users;
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
