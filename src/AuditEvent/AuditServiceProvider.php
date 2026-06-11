<?php

declare(strict_types=1);

namespace NeneField\AuditEvent;

use Closure;
use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\ClockInterface;
use Nene2\Http\JsonResponseFactory;
use Nene2\Log\RequestIdHolder;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Container\ContainerInterface;

/**
 * Registers the audit recorder factory (ADR 0014) plus the read/export side of
 * the audit trail (`GET /audit-events`, `GET /audit-events/export`).
 *
 * Mutating use cases inject the factory keyed by {@see self::RECORDER_FACTORY}
 * and call it with the transaction-bound executor inside `transactional()`:
 *
 *   $this->tx->transactional(function (DatabaseQueryExecutorInterface $exec) use (...) {
 *       $this->reports->save($exec, $report);
 *       ($this->auditFactory)($exec)->record($actorId, $orgId, 'report.created', 'Report', $id, null, $after);
 *   });
 */
final readonly class AuditServiceProvider implements ServiceProviderInterface
{
    /** Container id for `Closure(DatabaseQueryExecutorInterface): AuditRecorderInterface`. */
    public const RECORDER_FACTORY = 'nene_field.audit.recorder_factory';

    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                self::RECORDER_FACTORY,
                /** @return Closure(DatabaseQueryExecutorInterface): AuditRecorderInterface */
                static function (ContainerInterface $container): Closure {
                    $clock = $container->get(ClockInterface::class);

                    if (!$clock instanceof ClockInterface) {
                        throw new LogicException('Clock service is invalid.');
                    }

                    $requestIdHolder = $container->get(RequestIdHolder::class);

                    if (!$requestIdHolder instanceof RequestIdHolder) {
                        throw new LogicException('Request id holder service is invalid.');
                    }

                    return static function (DatabaseQueryExecutorInterface $executor) use ($clock, $requestIdHolder): AuditRecorderInterface {
                        $requestId = $requestIdHolder->get();

                        return new AuditRecorder($executor, $clock, $requestId !== '' ? $requestId : null);
                    };
                },
            )
            ->set(
                AuditEventRepositoryInterface::class,
                static fn (ContainerInterface $c): AuditEventRepositoryInterface => new PdoAuditEventRepository(self::executor($c)),
            )
            ->set(
                AuditEventCsvFormatter::class,
                static fn (ContainerInterface $c): AuditEventCsvFormatter => new AuditEventCsvFormatter(),
            )
            ->set(
                ListAuditEventsUseCaseInterface::class,
                static fn (ContainerInterface $c): ListAuditEventsUseCaseInterface => new ListAuditEventsUseCase(self::events($c)),
            )
            ->set(
                ExportAuditEventsUseCaseInterface::class,
                static fn (ContainerInterface $c): ExportAuditEventsUseCaseInterface => new ExportAuditEventsUseCase(
                    self::events($c),
                    self::formatter($c),
                    self::tx($c),
                    self::auditFactory($c),
                ),
            )
            ->set(
                ListAuditEventsHandler::class,
                static function (ContainerInterface $c): ListAuditEventsHandler {
                    $useCase = $c->get(ListAuditEventsUseCaseInterface::class);

                    if (!$useCase instanceof ListAuditEventsUseCaseInterface) {
                        throw new LogicException('List audit events use case service is invalid.');
                    }

                    return new ListAuditEventsHandler($useCase, self::json($c), self::problemDetails($c));
                },
            )
            ->set(
                ExportAuditEventsCsvHandler::class,
                static function (ContainerInterface $c): ExportAuditEventsCsvHandler {
                    $useCase = $c->get(ExportAuditEventsUseCaseInterface::class);

                    if (!$useCase instanceof ExportAuditEventsUseCaseInterface) {
                        throw new LogicException('Export audit events use case service is invalid.');
                    }

                    return new ExportAuditEventsCsvHandler($useCase, self::psr17($c), self::problemDetails($c));
                },
            )
            ->set(
                AuditEventRouteRegistrar::class,
                static function (ContainerInterface $c): AuditEventRouteRegistrar {
                    $list = $c->get(ListAuditEventsHandler::class);
                    $export = $c->get(ExportAuditEventsCsvHandler::class);

                    if (!$list instanceof ListAuditEventsHandler || !$export instanceof ExportAuditEventsCsvHandler) {
                        throw new LogicException('Audit event handler services are invalid.');
                    }

                    return new AuditEventRouteRegistrar($list, $export);
                },
            );
    }

    private static function events(ContainerInterface $c): AuditEventRepositoryInterface
    {
        $events = $c->get(AuditEventRepositoryInterface::class);

        if (!$events instanceof AuditEventRepositoryInterface) {
            throw new LogicException('Audit event repository service is invalid.');
        }

        return $events;
    }

    private static function formatter(ContainerInterface $c): AuditEventCsvFormatter
    {
        $formatter = $c->get(AuditEventCsvFormatter::class);

        if (!$formatter instanceof AuditEventCsvFormatter) {
            throw new LogicException('Audit event CSV formatter service is invalid.');
        }

        return $formatter;
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
     * @return Closure(DatabaseQueryExecutorInterface): AuditRecorderInterface
     */
    private static function auditFactory(ContainerInterface $c): Closure
    {
        $factory = $c->get(self::RECORDER_FACTORY);

        if (!$factory instanceof Closure) {
            throw new LogicException('Audit recorder factory service is invalid.');
        }

        return $factory;
    }

    private static function json(ContainerInterface $c): JsonResponseFactory
    {
        $json = $c->get(JsonResponseFactory::class);

        if (!$json instanceof JsonResponseFactory) {
            throw new LogicException('JSON response factory service is invalid.');
        }

        return $json;
    }

    private static function psr17(ContainerInterface $c): Psr17Factory
    {
        $psr17 = $c->get(Psr17Factory::class);

        if (!$psr17 instanceof Psr17Factory) {
            throw new LogicException('PSR-17 factory service is invalid.');
        }

        return $psr17;
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
