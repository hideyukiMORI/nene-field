<?php

declare(strict_types=1);

namespace NeneField\Organization\Resolution;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\RequestScopedHolder;
use NeneField\Organization\OrganizationRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Resolves the current organization from the request and stores its UUID in a
 * request-scoped holder for downstream repositories (multi-tenancy.md / ADR 0013).
 *
 * Bypass paths (health, auth, superadmin org management) skip resolution and pass
 * through with the holder left unset; repositories on those routes must not read it.
 *
 * Resolution: strategy → slug/identifier → findBySlug() ?? findByCustomDomain().
 * 404 `org-not-resolved` (no identifier) / 404 `org-not-found` / 403 `org-inactive`.
 */
final readonly class OrgResolverMiddleware implements MiddlewareInterface
{
    /** @var list<string> */
    private const BYPASS_PREFIXES = [
        '/health',
        '/machine/health',
        '/auth/',
        '/organizations',
        '/superadmin/',
    ];

    /**
     * @param RequestScopedHolder<string> $orgId
     */
    public function __construct(
        private RequestScopedHolder $orgId,
        private OrganizationRepositoryInterface $repository,
        private ProblemDetailsResponseFactory $problemDetails,
        private OrgResolutionStrategyInterface $strategy,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();

        foreach (self::BYPASS_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return $handler->handle($request);
            }
        }

        $identifier = $this->strategy->resolve($request);

        if ($identifier === null) {
            return $this->problemDetails->create(
                $request,
                'org-not-resolved',
                'Organization Not Resolved',
                404,
                'Could not determine the organization for this request.',
            );
        }

        $organization = $this->repository->findBySlug($identifier)
            ?? $this->repository->findByCustomDomain($identifier);

        if ($organization === null) {
            return $this->problemDetails->create(
                $request,
                'org-not-found',
                'Organization Not Found',
                404,
                "No organization found for '{$identifier}'.",
            );
        }

        if (!$organization->isActive) {
            return $this->problemDetails->create(
                $request,
                'org-inactive',
                'Organization Inactive',
                403,
                'This organization is currently inactive.',
            );
        }

        $this->orgId->set($organization->organizationId);

        return $handler->handle(
            $request
                ->withAttribute('nene_field.org.id', $organization->organizationId)
                ->withAttribute('nene_field.org.slug', $organization->slug),
        );
    }
}
