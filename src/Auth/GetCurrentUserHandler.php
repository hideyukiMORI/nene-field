<?php

declare(strict_types=1);

namespace NeneField\Auth;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use NeneField\User\UserResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * GET /auth/me — returns the authenticated user, scoped to their token org.
 */
final readonly class GetCurrentUserHandler implements RequestHandlerInterface
{
    public function __construct(
        private GetCurrentUserUseCaseInterface $useCase,
        private JsonResponseFactory $json,
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $organizationId = AuthContext::organizationId($request);
        $userId = AuthContext::userId($request);

        $user = ($organizationId !== null && $userId !== null)
            ? $this->useCase->execute($organizationId, $userId)
            : null;

        if ($user === null) {
            return $this->problemDetails->create(
                $request,
                'unauthorized',
                'Unauthorized',
                401,
                'The authenticated user no longer exists.',
            );
        }

        return $this->json->create(UserResponse::toArray($user));
    }
}
