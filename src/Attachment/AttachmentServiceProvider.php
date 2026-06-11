<?php

declare(strict_types=1);

namespace NeneField\Attachment;

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
use NeneField\Http\RuntimeServiceProvider;
use NeneField\Report\ReportRepositoryInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Container\ContainerInterface;

final readonly class AttachmentServiceProvider implements ServiceProviderInterface
{
    /** Env var for the storage root; defaults to `{projectRoot}/var/attachments`. */
    private const STORAGE_PATH_ENV = 'NENE_FIELD_STORAGE_PATH';

    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                AttachmentRepositoryInterface::class,
                static fn (ContainerInterface $c): AttachmentRepositoryInterface => new PdoAttachmentRepository(self::executor($c)),
            )
            ->set(
                AttachmentStorageInterface::class,
                static fn (ContainerInterface $c): AttachmentStorageInterface => new LocalAttachmentStorage(self::storageRoot($c)),
            )
            ->set(
                UploadAttachmentUseCaseInterface::class,
                static fn (ContainerInterface $c): UploadAttachmentUseCaseInterface => new UploadAttachmentUseCase(
                    self::reports($c),
                    self::attachments($c),
                    self::storage($c),
                    self::tx($c),
                    self::auditFactory($c),
                    self::clock($c),
                ),
            )
            ->set(
                DownloadAttachmentUseCaseInterface::class,
                static fn (ContainerInterface $c): DownloadAttachmentUseCaseInterface => new DownloadAttachmentUseCase(
                    self::reports($c),
                    self::attachments($c),
                    self::storage($c),
                ),
            )
            ->set(
                DeleteAttachmentUseCaseInterface::class,
                static fn (ContainerInterface $c): DeleteAttachmentUseCaseInterface => new DeleteAttachmentUseCase(
                    self::reports($c),
                    self::attachments($c),
                    self::storage($c),
                    self::tx($c),
                    self::auditFactory($c),
                ),
            )
            ->set(
                UploadAttachmentHandler::class,
                static function (ContainerInterface $c): UploadAttachmentHandler {
                    $useCase = $c->get(UploadAttachmentUseCaseInterface::class);

                    if (!$useCase instanceof UploadAttachmentUseCaseInterface) {
                        throw new LogicException('Upload attachment use case service is invalid.');
                    }

                    return new UploadAttachmentHandler($useCase, self::json($c), self::problemDetails($c));
                },
            )
            ->set(
                DownloadAttachmentHandler::class,
                static function (ContainerInterface $c): DownloadAttachmentHandler {
                    $useCase = $c->get(DownloadAttachmentUseCaseInterface::class);

                    if (!$useCase instanceof DownloadAttachmentUseCaseInterface) {
                        throw new LogicException('Download attachment use case service is invalid.');
                    }

                    return new DownloadAttachmentHandler($useCase, self::psr17($c), self::problemDetails($c));
                },
            )
            ->set(
                DeleteAttachmentHandler::class,
                static function (ContainerInterface $c): DeleteAttachmentHandler {
                    $useCase = $c->get(DeleteAttachmentUseCaseInterface::class);

                    if (!$useCase instanceof DeleteAttachmentUseCaseInterface) {
                        throw new LogicException('Delete attachment use case service is invalid.');
                    }

                    return new DeleteAttachmentHandler($useCase, self::json($c), self::problemDetails($c));
                },
            )
            ->set(
                AttachmentRouteRegistrar::class,
                static function (ContainerInterface $c): AttachmentRouteRegistrar {
                    $upload = $c->get(UploadAttachmentHandler::class);
                    $download = $c->get(DownloadAttachmentHandler::class);
                    $delete = $c->get(DeleteAttachmentHandler::class);

                    if (
                        !$upload instanceof UploadAttachmentHandler
                        || !$download instanceof DownloadAttachmentHandler
                        || !$delete instanceof DeleteAttachmentHandler
                    ) {
                        throw new LogicException('Attachment handler services are invalid.');
                    }

                    return new AttachmentRouteRegistrar($upload, $download, $delete);
                },
            );
    }

    private static function storageRoot(ContainerInterface $c): string
    {
        $env = $_SERVER[self::STORAGE_PATH_ENV] ?? $_ENV[self::STORAGE_PATH_ENV] ?? getenv(self::STORAGE_PATH_ENV);

        if (is_string($env) && $env !== '') {
            return $env;
        }

        $projectRoot = $c->get(RuntimeServiceProvider::PROJECT_ROOT);

        if (!is_string($projectRoot) || $projectRoot === '') {
            throw new LogicException('Project root service is invalid.');
        }

        return $projectRoot . '/var/attachments';
    }

    private static function reports(ContainerInterface $c): ReportRepositoryInterface
    {
        $reports = $c->get(ReportRepositoryInterface::class);

        if (!$reports instanceof ReportRepositoryInterface) {
            throw new LogicException('Report repository service is invalid.');
        }

        return $reports;
    }

    private static function attachments(ContainerInterface $c): AttachmentRepositoryInterface
    {
        $attachments = $c->get(AttachmentRepositoryInterface::class);

        if (!$attachments instanceof AttachmentRepositoryInterface) {
            throw new LogicException('Attachment repository service is invalid.');
        }

        return $attachments;
    }

    private static function storage(ContainerInterface $c): AttachmentStorageInterface
    {
        $storage = $c->get(AttachmentStorageInterface::class);

        if (!$storage instanceof AttachmentStorageInterface) {
            throw new LogicException('Attachment storage service is invalid.');
        }

        return $storage;
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
