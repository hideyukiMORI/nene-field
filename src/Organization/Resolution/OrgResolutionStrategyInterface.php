<?php

declare(strict_types=1);

namespace NeneField\Organization\Resolution;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Resolves the organization identifier (slug or custom domain) from a request.
 *
 * Implementations: {@see EnvResolutionStrategy} (single-tenant / Tier A),
 * {@see SubdomainResolutionStrategy}, {@see PathPrefixResolutionStrategy} (Tier B).
 * See docs/development/multi-tenancy.md (ADR 0013).
 */
interface OrgResolutionStrategyInterface
{
    /** Returns the org slug / custom-domain identifier, or null when undeterminable. */
    public function resolve(ServerRequestInterface $request): ?string;
}
