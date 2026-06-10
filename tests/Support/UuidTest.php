<?php

declare(strict_types=1);

namespace NeneField\Tests\Support;

use NeneField\Support\Uuid;
use PHPUnit\Framework\TestCase;

final class UuidTest extends TestCase
{
    public function test_generates_rfc4122_v4_format(): void
    {
        $uuid = Uuid::v4();

        self::assertMatchesRegularExpression(
            '/\A[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/',
            $uuid,
        );
    }

    public function test_generates_unique_values(): void
    {
        self::assertNotSame(Uuid::v4(), Uuid::v4());
    }
}
