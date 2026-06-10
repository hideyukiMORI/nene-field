<?php

declare(strict_types=1);

namespace NeneField\Tests\Support;

use DateTimeImmutable;
use Nene2\Http\ClockInterface;

/**
 * Deterministic {@see ClockInterface} returning a fixed instant for tests.
 */
final readonly class FixedClock implements ClockInterface
{
    public function __construct(
        private DateTimeImmutable $now,
    ) {
    }

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }
}
