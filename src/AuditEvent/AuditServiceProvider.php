<?php

declare(strict_types=1);

namespace NeneField\AuditEvent;

use Closure;
use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Http\ClockInterface;
use Nene2\Log\RequestIdHolder;
use Psr\Container\ContainerInterface;

/**
 * Registers the audit recorder factory (ADR 0014). Mutating use cases inject the
 * factory keyed by {@see self::RECORDER_FACTORY} and call it with the
 * transaction-bound executor inside `transactional()`:
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
        $builder->set(
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
        );
    }
}
