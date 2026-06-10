<?php

declare(strict_types=1);

namespace NeneField\Auth;

use Nene2\Error\ProblemDetailsResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Cross-checks the authenticated user's organization (token `org` claim) against
 * the organization resolved from the request (multi-tenancy.md §5 / ADR 0013).
 * Runs after OrgResolverMiddleware (sets `nene_field.org.id`) and
 * BearerTokenMiddleware (sets `nene2.auth.claims`).
 *
 * A member of org A must not operate on org B's context even with a valid token.
 * Superadmin (token `org` null) is exempt. Routes without a resolved org (bypass)
 * or without claims (public) pass through.
 */
final readonly class OrgGuardMiddleware implements MiddlewareInterface
{
    private const ORG_ID_ATTRIBUTE = 'nene_field.org.id';
    private const CLAIMS_ATTRIBUTE = 'nene2.auth.claims';

    public function __construct(
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $resolvedOrgId = $request->getAttribute(self::ORG_ID_ATTRIBUTE);

        // No resolved org (bypass route) → nothing to guard here.
        if (!is_string($resolvedOrgId)) {
            return $handler->handle($request);
        }

        $claims = $request->getAttribute(self::CLAIMS_ATTRIBUTE);

        // No verified claims (public route, e.g. /auth/login) → auth decides later.
        if (!is_array($claims)) {
            return $handler->handle($request);
        }

        $tokenOrg = $claims['org'] ?? null;

        // Superadmin (org null) operates cross-tenant — but only with the matching role.
        if ($tokenOrg === null) {
            if (($claims['role'] ?? null) === Role::Superadmin->value) {
                return $handler->handle($request);
            }

            return $this->forbidden($request);
        }

        if (!is_string($tokenOrg) || $tokenOrg !== $resolvedOrgId) {
            return $this->forbidden($request);
        }

        return $handler->handle($request);
    }

    private function forbidden(ServerRequestInterface $request): ResponseInterface
    {
        return $this->problemDetails->create(
            $request,
            'forbidden',
            'Forbidden',
            403,
            'Your account does not belong to this organization.',
        );
    }
}
