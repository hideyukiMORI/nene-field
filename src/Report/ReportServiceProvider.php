<?php

declare(strict_types=1);

namespace NeneField\Report;

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

final readonly class ReportServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                ReportRepositoryInterface::class,
                static fn (ContainerInterface $c): ReportRepositoryInterface => new PdoReportRepository(self::executor($c)),
            )
            ->set(
                CreateReportUseCaseInterface::class,
                static fn (ContainerInterface $c): CreateReportUseCaseInterface
                    => new CreateReportUseCase(self::reports($c), self::tx($c), self::auditFactory($c), self::clock($c)),
            )
            ->set(
                GetReportUseCaseInterface::class,
                static fn (ContainerInterface $c): GetReportUseCaseInterface => new GetReportUseCase(self::reports($c)),
            )
            ->set(
                ListReportsUseCaseInterface::class,
                static fn (ContainerInterface $c): ListReportsUseCaseInterface => new ListReportsUseCase(self::reports($c)),
            )
            ->set(
                ApproveReportUseCaseInterface::class,
                static fn (ContainerInterface $c): ApproveReportUseCaseInterface
                    => new ApproveReportUseCase(self::reports($c), self::tx($c), self::auditFactory($c), self::clock($c)),
            )
            ->set(
                RejectReportUseCaseInterface::class,
                static fn (ContainerInterface $c): RejectReportUseCaseInterface
                    => new RejectReportUseCase(self::reports($c), self::tx($c), self::auditFactory($c), self::clock($c)),
            )
            ->set(
                UpdateReportUseCaseInterface::class,
                static fn (ContainerInterface $c): UpdateReportUseCaseInterface
                    => new UpdateReportUseCase(self::reports($c), self::tx($c), self::auditFactory($c), self::clock($c)),
            )
            ->set(
                DeleteReportUseCaseInterface::class,
                static fn (ContainerInterface $c): DeleteReportUseCaseInterface
                    => new DeleteReportUseCase(self::reports($c), self::tx($c), self::auditFactory($c)),
            )
            ->set(
                SubmitReportUseCaseInterface::class,
                static fn (ContainerInterface $c): SubmitReportUseCaseInterface
                    => new SubmitReportUseCase(self::reports($c), self::tx($c), self::auditFactory($c), self::clock($c)),
            )
            ->set(
                CreateReportHandler::class,
                static function (ContainerInterface $c): CreateReportHandler {
                    $useCase = $c->get(CreateReportUseCaseInterface::class);

                    if (!$useCase instanceof CreateReportUseCaseInterface) {
                        throw new LogicException('Create report use case service is invalid.');
                    }

                    return new CreateReportHandler($useCase, self::json($c), self::problemDetails($c));
                },
            )
            ->set(
                GetReportHandler::class,
                static function (ContainerInterface $c): GetReportHandler {
                    $useCase = $c->get(GetReportUseCaseInterface::class);

                    if (!$useCase instanceof GetReportUseCaseInterface) {
                        throw new LogicException('Get report use case service is invalid.');
                    }

                    return new GetReportHandler($useCase, self::json($c), self::problemDetails($c));
                },
            )
            ->set(
                UpdateReportHandler::class,
                static function (ContainerInterface $c): UpdateReportHandler {
                    $useCase = $c->get(UpdateReportUseCaseInterface::class);

                    if (!$useCase instanceof UpdateReportUseCaseInterface) {
                        throw new LogicException('Update report use case service is invalid.');
                    }

                    return new UpdateReportHandler($useCase, self::json($c), self::problemDetails($c));
                },
            )
            ->set(
                DeleteReportHandler::class,
                static function (ContainerInterface $c): DeleteReportHandler {
                    $useCase = $c->get(DeleteReportUseCaseInterface::class);

                    if (!$useCase instanceof DeleteReportUseCaseInterface) {
                        throw new LogicException('Delete report use case service is invalid.');
                    }

                    return new DeleteReportHandler($useCase, self::json($c), self::problemDetails($c));
                },
            )
            ->set(
                SubmitReportHandler::class,
                static function (ContainerInterface $c): SubmitReportHandler {
                    $useCase = $c->get(SubmitReportUseCaseInterface::class);

                    if (!$useCase instanceof SubmitReportUseCaseInterface) {
                        throw new LogicException('Submit report use case service is invalid.');
                    }

                    return new SubmitReportHandler($useCase, self::json($c), self::problemDetails($c));
                },
            )
            ->set(
                ListReportsHandler::class,
                static function (ContainerInterface $c): ListReportsHandler {
                    $useCase = $c->get(ListReportsUseCaseInterface::class);

                    if (!$useCase instanceof ListReportsUseCaseInterface) {
                        throw new LogicException('List reports use case service is invalid.');
                    }

                    return new ListReportsHandler($useCase, self::json($c), self::problemDetails($c));
                },
            )
            ->set(
                ApproveReportHandler::class,
                static function (ContainerInterface $c): ApproveReportHandler {
                    $useCase = $c->get(ApproveReportUseCaseInterface::class);

                    if (!$useCase instanceof ApproveReportUseCaseInterface) {
                        throw new LogicException('Approve report use case service is invalid.');
                    }

                    return new ApproveReportHandler($useCase, self::json($c), self::problemDetails($c));
                },
            )
            ->set(
                RejectReportHandler::class,
                static function (ContainerInterface $c): RejectReportHandler {
                    $useCase = $c->get(RejectReportUseCaseInterface::class);

                    if (!$useCase instanceof RejectReportUseCaseInterface) {
                        throw new LogicException('Reject report use case service is invalid.');
                    }

                    return new RejectReportHandler($useCase, self::json($c), self::problemDetails($c));
                },
            )
            ->set(
                ReportRouteRegistrar::class,
                static function (ContainerInterface $c): ReportRouteRegistrar {
                    $list = $c->get(ListReportsHandler::class);
                    $create = $c->get(CreateReportHandler::class);
                    $get = $c->get(GetReportHandler::class);
                    $update = $c->get(UpdateReportHandler::class);
                    $delete = $c->get(DeleteReportHandler::class);
                    $submit = $c->get(SubmitReportHandler::class);
                    $approve = $c->get(ApproveReportHandler::class);
                    $reject = $c->get(RejectReportHandler::class);

                    if (
                        !$list instanceof ListReportsHandler
                        || !$create instanceof CreateReportHandler
                        || !$get instanceof GetReportHandler
                        || !$update instanceof UpdateReportHandler
                        || !$delete instanceof DeleteReportHandler
                        || !$submit instanceof SubmitReportHandler
                        || !$approve instanceof ApproveReportHandler
                        || !$reject instanceof RejectReportHandler
                    ) {
                        throw new LogicException('Report handler services are invalid.');
                    }

                    return new ReportRouteRegistrar($list, $create, $get, $update, $delete, $submit, $approve, $reject);
                },
            );
    }

    private static function reports(ContainerInterface $c): ReportRepositoryInterface
    {
        $reports = $c->get(ReportRepositoryInterface::class);

        if (!$reports instanceof ReportRepositoryInterface) {
            throw new LogicException('Report repository service is invalid.');
        }

        return $reports;
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
