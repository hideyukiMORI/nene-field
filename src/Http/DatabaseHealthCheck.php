<?php

declare(strict_types=1);

namespace NeneField\Http;

use Nene2\Database\DatabaseConnectionFactoryInterface;
use Nene2\Http\HealthCheckInterface;
use Nene2\Http\HealthStatus;
use Throwable;

/**
 * Reports database connectivity for `GET /health`.
 *
 * Adapted from the framework's reference Nene2\Example\Health\DatabaseHealthCheck
 * (which is documented as copy-and-adapt, not for direct import).
 */
final readonly class DatabaseHealthCheck implements HealthCheckInterface
{
    public function __construct(
        private DatabaseConnectionFactoryInterface $connectionFactory,
    ) {
    }

    public function name(): string
    {
        return 'database';
    }

    public function check(): HealthStatus
    {
        try {
            $this->connectionFactory->create()->query('SELECT 1');

            return HealthStatus::Ok;
        } catch (Throwable) {
            return HealthStatus::Error;
        }
    }
}
