<?php

declare(strict_types=1);

namespace NeneField\Auth;

use Nene2\Http\JsonResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * POST /auth/logout — stateless. JWTs are self-contained and expire by `exp`;
 * the client discards its token. A server-side token blocklist for immediate
 * revocation is a future enhancement.
 */
final readonly class LogoutHandler implements RequestHandlerInterface
{
    public function __construct(
        private JsonResponseFactory $json,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->json->createEmpty(204);
    }
}
