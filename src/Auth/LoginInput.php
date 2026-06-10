<?php

declare(strict_types=1);

namespace NeneField\Auth;

/**
 * Login is tenant-scoped: the organization is resolved from the request
 * (OrgResolverMiddleware) before the handler builds this input.
 */
final readonly class LoginInput
{
    public function __construct(
        public string $organizationId,
        public string $email,
        public string $password,
    ) {
    }
}
