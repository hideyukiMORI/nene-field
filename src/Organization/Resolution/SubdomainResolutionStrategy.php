<?php

declare(strict_types=1);

namespace NeneField\Organization\Resolution;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Resolves the org slug from the request subdomain, e.g.
 * `yamada.field.example.com` → `yamada` (base domain `field.example.com`).
 *
 * Returns null when the host has no subdomain above the base domain or when the
 * tail does not match the configured base domain.
 */
final readonly class SubdomainResolutionStrategy implements OrgResolutionStrategyInterface
{
    public function __construct(
        private string $baseDomain,
    ) {
    }

    public function resolve(ServerRequestInterface $request): ?string
    {
        $host = $request->getUri()->getHost();

        if (str_contains($host, ':')) {
            $host = explode(':', $host)[0];
        }

        if ($host === '') {
            return null;
        }

        $baseParts = explode('.', $this->baseDomain);
        $hostParts = explode('.', $host);

        if (count($hostParts) <= count($baseParts)) {
            return null;
        }

        $tail = array_slice($hostParts, -count($baseParts));

        if ($tail !== $baseParts) {
            return null;
        }

        return $hostParts[0];
    }
}
