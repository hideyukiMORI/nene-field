<?php

declare(strict_types=1);

namespace NeneField\Auth;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Reads the authenticated principal from the verified token claims stored on the
 * request by the framework {@see \Nene2\Auth\BearerTokenMiddleware}
 * (`nene2.auth.claims`). Claims are issued by {@see LoginUseCase}:
 * `sub` (user UUID), `role`, `org` (organization UUID).
 */
final class AuthContext
{
    private const CLAIMS_ATTRIBUTE = 'nene2.auth.claims';

    public static function userId(ServerRequestInterface $request): ?string
    {
        $value = self::claim($request, 'sub');

        return is_string($value) && $value !== '' ? $value : null;
    }

    public static function role(ServerRequestInterface $request): ?Role
    {
        $value = self::claim($request, 'role');

        return is_string($value) ? Role::tryFrom($value) : null;
    }

    public static function organizationId(ServerRequestInterface $request): ?string
    {
        $value = self::claim($request, 'org');

        return is_string($value) && $value !== '' ? $value : null;
    }

    private static function claim(ServerRequestInterface $request, string $key): mixed
    {
        $claims = $request->getAttribute(self::CLAIMS_ATTRIBUTE);

        return is_array($claims) ? ($claims[$key] ?? null) : null;
    }
}
