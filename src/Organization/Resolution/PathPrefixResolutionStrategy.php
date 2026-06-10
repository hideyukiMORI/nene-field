<?php

declare(strict_types=1);

namespace NeneField\Organization\Resolution;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Resolves the org slug from the first path segment, e.g.
 * `/yamada/reports` → `yamada`. Returns null when the path has no segment.
 *
 * Note: when this strategy is active, the leading `/{slug}` segment is the tenant
 * key; route registration is expected to account for it (Tier B path mode).
 */
final readonly class PathPrefixResolutionStrategy implements OrgResolutionStrategyInterface
{
    public function resolve(ServerRequestInterface $request): ?string
    {
        $path = $request->getUri()->getPath();
        $trimmed = ltrim($path, '/');

        if ($trimmed === '') {
            return null;
        }

        $parts = explode('/', $trimmed, 2);
        $slug = $parts[0];

        return $slug !== '' ? $slug : null;
    }
}
