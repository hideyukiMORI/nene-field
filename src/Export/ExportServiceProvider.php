<?php

declare(strict_types=1);

namespace NeneField\Export;

use Closure;
use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use NeneField\AuditEvent\AuditServiceProvider;
use NeneField\Report\ReportRepositoryInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Container\ContainerInterface;

final readonly class ExportServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                ReportCsvFormatter::class,
                static fn (ContainerInterface $c): ReportCsvFormatter => new ReportCsvFormatter(),
            )
            ->set(
                ExportReportsUseCaseInterface::class,
                static fn (ContainerInterface $c): ExportReportsUseCaseInterface => new ExportReportsUseCase(
                    self::reports($c),
                    self::formatter($c),
                    self::tx($c),
                    self::auditFactory($c),
                ),
            )
            ->set(
                ExportReportsCsvHandler::class,
                static function (ContainerInterface $c): ExportReportsCsvHandler {
                    $useCase = $c->get(ExportReportsUseCaseInterface::class);

                    if (!$useCase instanceof ExportReportsUseCaseInterface) {
                        throw new LogicException('Export reports use case service is invalid.');
                    }

                    return new ExportReportsCsvHandler($useCase, self::psr17($c), self::problemDetails($c));
                },
            )
            ->set(
                ExportRouteRegistrar::class,
                static function (ContainerInterface $c): ExportRouteRegistrar {
                    $handler = $c->get(ExportReportsCsvHandler::class);

                    if (!$handler instanceof ExportReportsCsvHandler) {
                        throw new LogicException('Export reports handler service is invalid.');
                    }

                    return new ExportRouteRegistrar($handler);
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

    private static function formatter(ContainerInterface $c): ReportCsvFormatter
    {
        $formatter = $c->get(ReportCsvFormatter::class);

        if (!$formatter instanceof ReportCsvFormatter) {
            throw new LogicException('Report CSV formatter service is invalid.');
        }

        return $formatter;
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
