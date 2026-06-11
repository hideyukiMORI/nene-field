<?php

declare(strict_types=1);

namespace NeneField\Tests\Organization\Resolution;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\RequestScopedHolder;
use NeneField\Organization\Organization;
use NeneField\Organization\OrganizationRepositoryInterface;
use NeneField\Organization\Resolution\EnvResolutionStrategy;
use NeneField\Organization\Resolution\OrgResolverMiddleware;
use NeneField\Tests\Support\RecordingRequestHandler;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;

final class OrgResolverMiddlewareTest extends TestCase
{
    private Psr17Factory $psr17;

    /** @var RequestScopedHolder<string> */
    private RequestScopedHolder $orgId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->psr17 = new Psr17Factory();
        $this->orgId = new RequestScopedHolder();
    }

    public function test_resolves_active_org_sets_holder_and_passes_through(): void
    {
        $org = new Organization('org-uuid-1', '山田造園', 'yamada', true);
        $handler = new RecordingRequestHandler($this->psr17);

        $response = $this->middleware('yamada', [$org])->process(
            $this->psr17->createServerRequest('GET', 'http://localhost/reports'),
            $handler,
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($handler->called);
        self::assertTrue($this->orgId->isSet());
        self::assertSame('org-uuid-1', $this->orgId->get());

        $captured = $handler->request;
        if ($captured === null) {
            self::fail('Handler did not receive the request.');
        }
        self::assertSame('org-uuid-1', $captured->getAttribute('nene_field.org.id'));
        self::assertSame('yamada', $captured->getAttribute('nene_field.org.slug'));
    }

    public function test_bypasses_health_without_resolution(): void
    {
        $handler = new RecordingRequestHandler($this->psr17);

        // Empty slug would otherwise yield org-not-resolved; /health must bypass.
        $response = $this->middleware('', [])->process(
            $this->psr17->createServerRequest('GET', 'http://localhost/health'),
            $handler,
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($handler->called);
        self::assertFalse($this->orgId->isSet());
    }

    public function test_unresolved_identifier_returns_404(): void
    {
        $handler = new RecordingRequestHandler($this->psr17);

        $response = $this->middleware('', [])->process(
            $this->psr17->createServerRequest('GET', 'http://localhost/reports'),
            $handler,
        );

        self::assertSame(404, $response->getStatusCode());
        self::assertFalse($handler->called);
        self::assertStringContainsString('org-not-resolved', (string) $response->getBody());
    }

    public function test_unknown_org_returns_404(): void
    {
        $handler = new RecordingRequestHandler($this->psr17);

        $response = $this->middleware('ghost', [])->process(
            $this->psr17->createServerRequest('GET', 'http://localhost/reports'),
            $handler,
        );

        self::assertSame(404, $response->getStatusCode());
        self::assertFalse($handler->called);
        self::assertStringContainsString('org-not-found', (string) $response->getBody());
    }

    public function test_inactive_org_returns_403(): void
    {
        $org = new Organization('org-uuid-2', 'Closed Co', 'closed', false);
        $handler = new RecordingRequestHandler($this->psr17);

        $response = $this->middleware('closed', [$org])->process(
            $this->psr17->createServerRequest('GET', 'http://localhost/reports'),
            $handler,
        );

        self::assertSame(403, $response->getStatusCode());
        self::assertFalse($handler->called);
        self::assertStringContainsString('org-inactive', (string) $response->getBody());
    }

    /**
     * @param list<Organization> $orgs
     */
    private function middleware(string $slug, array $orgs): OrgResolverMiddleware
    {
        return new OrgResolverMiddleware(
            $this->orgId,
            $this->fakeRepository($orgs),
            new ProblemDetailsResponseFactory($this->psr17, $this->psr17, 'https://nene-field.dev/problems/'),
            new EnvResolutionStrategy($slug),
        );
    }

    /**
     * @param list<Organization> $orgs
     */
    private function fakeRepository(array $orgs): OrganizationRepositoryInterface
    {
        return new class ($orgs) implements OrganizationRepositoryInterface {
            /** @var array<string, Organization> */
            private array $bySlug = [];
            /** @var array<string, Organization> */
            private array $byDomain = [];

            /** @param list<Organization> $orgs */
            public function __construct(array $orgs)
            {
                foreach ($orgs as $org) {
                    $this->bySlug[$org->slug] = $org;
                    if ($org->customDomain !== null) {
                        $this->byDomain[$org->customDomain] = $org;
                    }
                }
            }

            public function findById(string $organizationId): ?Organization
            {
                foreach ($this->bySlug as $org) {
                    if ($org->organizationId === $organizationId) {
                        return $org;
                    }
                }

                return null;
            }

            public function findBySlug(string $slug): ?Organization
            {
                return $this->bySlug[$slug] ?? null;
            }

            public function findByCustomDomain(string $customDomain): ?Organization
            {
                return $this->byDomain[$customDomain] ?? null;
            }

            public function listAll(int $limit, int $offset): array
            {
                return array_slice(array_values($this->bySlug), $offset, $limit);
            }

            public function countAll(): int
            {
                return count($this->bySlug);
            }

            public function insert(DatabaseQueryExecutorInterface $executor, Organization $organization): void
            {
                $this->bySlug[$organization->slug] = $organization;
            }

            public function update(DatabaseQueryExecutorInterface $executor, Organization $organization): void
            {
                $this->bySlug[$organization->slug] = $organization;
            }
        };
    }

}
