<?php

declare(strict_types=1);

namespace NeneField\Organization\Resolution;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Resolves the org slug from a fixed configured value (NENE_FIELD_ORG_SLUG).
 *
 * For local development and single-organization (Tier A) installs where one
 * organization owns the whole instance. Returns null when no slug is configured.
 */
final readonly class EnvResolutionStrategy implements OrgResolutionStrategyInterface
{
    public function __construct(
        private string $orgSlug,
    ) {
    }

    public function resolve(ServerRequestInterface $request): ?string
    {
        return $this->orgSlug !== '' ? $this->orgSlug : null;
    }
}
