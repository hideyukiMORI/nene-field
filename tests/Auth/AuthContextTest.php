<?php

declare(strict_types=1);

namespace NeneField\Tests\Auth;

use NeneField\Auth\AuthContext;
use NeneField\Auth\Role;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;

final class AuthContextTest extends TestCase
{
    public function test_reads_claims_from_request(): void
    {
        $request = (new Psr17Factory())
            ->createServerRequest('GET', 'http://localhost/auth/me')
            ->withAttribute('nene2.auth.claims', ['sub' => 'user-1', 'role' => 'admin', 'org' => 'org-1']);

        self::assertSame('user-1', AuthContext::userId($request));
        self::assertSame(Role::Admin, AuthContext::role($request));
        self::assertSame('org-1', AuthContext::organizationId($request));
    }

    public function test_returns_null_without_claims(): void
    {
        $request = (new Psr17Factory())->createServerRequest('GET', 'http://localhost/auth/me');

        self::assertNull(AuthContext::userId($request));
        self::assertNull(AuthContext::role($request));
        self::assertNull(AuthContext::organizationId($request));
    }
}
