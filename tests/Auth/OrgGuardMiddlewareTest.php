<?php

declare(strict_types=1);

namespace NeneField\Tests\Auth;

use Nene2\Error\ProblemDetailsResponseFactory;
use NeneField\Auth\OrgGuardMiddleware;
use NeneField\Auth\Role;
use NeneField\Tests\Support\RecordingRequestHandler;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

final class OrgGuardMiddlewareTest extends TestCase
{
    private Psr17Factory $psr17;
    private OrgGuardMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->psr17 = new Psr17Factory();
        $this->middleware = new OrgGuardMiddleware(
            new ProblemDetailsResponseFactory($this->psr17, $this->psr17, 'https://nene-field.dev/problems/'),
        );
    }

    public function test_passes_when_token_org_matches_resolved_org(): void
    {
        $handler = new RecordingRequestHandler($this->psr17);
        $response = $this->middleware->process(
            $this->request('org-1', ['org' => 'org-1', 'role' => 'admin']),
            $handler,
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($handler->called);
    }

    public function test_forbids_cross_tenant_token(): void
    {
        $handler = new RecordingRequestHandler($this->psr17);
        $response = $this->middleware->process(
            $this->request('org-1', ['org' => 'org-2', 'role' => 'admin']),
            $handler,
        );

        self::assertSame(403, $response->getStatusCode());
        self::assertFalse($handler->called);
        self::assertStringContainsString('forbidden', (string) $response->getBody());
    }

    public function test_superadmin_with_null_org_passes(): void
    {
        $handler = new RecordingRequestHandler($this->psr17);
        $response = $this->middleware->process(
            $this->request('org-1', ['org' => null, 'role' => Role::Superadmin->value]),
            $handler,
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($handler->called);
    }

    public function test_null_org_without_superadmin_role_is_forbidden(): void
    {
        $handler = new RecordingRequestHandler($this->psr17);
        $response = $this->middleware->process(
            $this->request('org-1', ['org' => null, 'role' => 'admin']),
            $handler,
        );

        self::assertSame(403, $response->getStatusCode());
        self::assertFalse($handler->called);
    }

    public function test_passes_when_no_resolved_org(): void
    {
        $handler = new RecordingRequestHandler($this->psr17);
        $response = $this->middleware->process(
            $this->psr17->createServerRequest('GET', 'http://localhost/health'),
            $handler,
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($handler->called);
    }

    public function test_passes_when_no_claims(): void
    {
        $handler = new RecordingRequestHandler($this->psr17);
        $response = $this->middleware->process(
            $this->psr17->createServerRequest('GET', 'http://localhost/auth/login')
                ->withAttribute('nene_field.org.id', 'org-1'),
            $handler,
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($handler->called);
    }

    /**
     * @param array<string, mixed> $claims
     */
    private function request(string $resolvedOrgId, array $claims): ServerRequestInterface
    {
        return $this->psr17->createServerRequest('GET', 'http://localhost/reports')
            ->withAttribute('nene_field.org.id', $resolvedOrgId)
            ->withAttribute('nene2.auth.claims', $claims);
    }
}
