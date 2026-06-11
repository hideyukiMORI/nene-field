<?php

declare(strict_types=1);

namespace NeneField\Template;

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

final readonly class TemplateServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                TemplateRepositoryInterface::class,
                static fn (ContainerInterface $c): TemplateRepositoryInterface => new PdoTemplateRepository(self::executor($c)),
            )
            ->set(
                ListTemplatesUseCaseInterface::class,
                static fn (ContainerInterface $c): ListTemplatesUseCaseInterface => new ListTemplatesUseCase(self::templates($c)),
            )
            ->set(
                GetTemplateUseCaseInterface::class,
                static fn (ContainerInterface $c): GetTemplateUseCaseInterface => new GetTemplateUseCase(self::templates($c)),
            )
            ->set(
                CreateTemplateUseCaseInterface::class,
                static fn (ContainerInterface $c): CreateTemplateUseCaseInterface
                    => new CreateTemplateUseCase(self::templates($c), self::tx($c), self::auditFactory($c), self::clock($c)),
            )
            ->set(
                UpdateTemplateUseCaseInterface::class,
                static fn (ContainerInterface $c): UpdateTemplateUseCaseInterface
                    => new UpdateTemplateUseCase(self::templates($c), self::tx($c), self::auditFactory($c), self::clock($c)),
            )
            ->set(
                DeleteTemplateUseCaseInterface::class,
                static fn (ContainerInterface $c): DeleteTemplateUseCaseInterface
                    => new DeleteTemplateUseCase(self::templates($c), self::tx($c), self::auditFactory($c)),
            )
            ->set(
                ListTemplatesHandler::class,
                static function (ContainerInterface $c): ListTemplatesHandler {
                    $useCase = $c->get(ListTemplatesUseCaseInterface::class);

                    if (!$useCase instanceof ListTemplatesUseCaseInterface) {
                        throw new LogicException('List templates use case service is invalid.');
                    }

                    return new ListTemplatesHandler($useCase, self::json($c), self::problemDetails($c));
                },
            )
            ->set(
                GetTemplateHandler::class,
                static function (ContainerInterface $c): GetTemplateHandler {
                    $useCase = $c->get(GetTemplateUseCaseInterface::class);

                    if (!$useCase instanceof GetTemplateUseCaseInterface) {
                        throw new LogicException('Get template use case service is invalid.');
                    }

                    return new GetTemplateHandler($useCase, self::json($c), self::problemDetails($c));
                },
            )
            ->set(
                CreateTemplateHandler::class,
                static function (ContainerInterface $c): CreateTemplateHandler {
                    $useCase = $c->get(CreateTemplateUseCaseInterface::class);

                    if (!$useCase instanceof CreateTemplateUseCaseInterface) {
                        throw new LogicException('Create template use case service is invalid.');
                    }

                    return new CreateTemplateHandler($useCase, self::json($c), self::problemDetails($c));
                },
            )
            ->set(
                UpdateTemplateHandler::class,
                static function (ContainerInterface $c): UpdateTemplateHandler {
                    $useCase = $c->get(UpdateTemplateUseCaseInterface::class);

                    if (!$useCase instanceof UpdateTemplateUseCaseInterface) {
                        throw new LogicException('Update template use case service is invalid.');
                    }

                    return new UpdateTemplateHandler($useCase, self::json($c), self::problemDetails($c));
                },
            )
            ->set(
                DeleteTemplateHandler::class,
                static function (ContainerInterface $c): DeleteTemplateHandler {
                    $useCase = $c->get(DeleteTemplateUseCaseInterface::class);

                    if (!$useCase instanceof DeleteTemplateUseCaseInterface) {
                        throw new LogicException('Delete template use case service is invalid.');
                    }

                    return new DeleteTemplateHandler($useCase, self::json($c), self::problemDetails($c));
                },
            )
            ->set(
                TemplateRouteRegistrar::class,
                static function (ContainerInterface $c): TemplateRouteRegistrar {
                    $list = $c->get(ListTemplatesHandler::class);
                    $create = $c->get(CreateTemplateHandler::class);
                    $get = $c->get(GetTemplateHandler::class);
                    $update = $c->get(UpdateTemplateHandler::class);
                    $delete = $c->get(DeleteTemplateHandler::class);

                    if (
                        !$list instanceof ListTemplatesHandler
                        || !$create instanceof CreateTemplateHandler
                        || !$get instanceof GetTemplateHandler
                        || !$update instanceof UpdateTemplateHandler
                        || !$delete instanceof DeleteTemplateHandler
                    ) {
                        throw new LogicException('Template handler services are invalid.');
                    }

                    return new TemplateRouteRegistrar($list, $create, $get, $update, $delete);
                },
            );
    }

    private static function templates(ContainerInterface $c): TemplateRepositoryInterface
    {
        $templates = $c->get(TemplateRepositoryInterface::class);

        if (!$templates instanceof TemplateRepositoryInterface) {
            throw new LogicException('Template repository service is invalid.');
        }

        return $templates;
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
