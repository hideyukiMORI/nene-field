<?php

declare(strict_types=1);

namespace NeneField\User;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use NeneField\Auth\AuthContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * GET /users/{user_id} — fetch a user in the caller's organization (admin only).
 */
final readonly class GetUserHandler implements RequestHandlerInterface
{
    public function __construct(
        private GetUserUseCaseInterface $useCase,
        private JsonResponseFactory $json,
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $organizationId = AuthContext::organizationId($request);
        $role = AuthContext::role($request);

        if ($organizationId === null || $role === null) {
            return $this->problemDetails->create($request, 'unauthorized', 'Unauthorized', 401, 'Authentication required.');
        }

        if (!$role->canManageOrganization()) {
            return $this->problemDetails->create($request, 'forbidden', 'Forbidden', 403, 'Organization management is required.');
        }

        $params = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $userId = is_array($params) && is_string($params['user_id'] ?? null) ? $params['user_id'] : '';

        $user = $this->useCase->execute($organizationId, $userId);

        if ($user === null) {
            return $this->problemDetails->create($request, 'user-not-found', 'User Not Found', 404, 'The user was not found.');
        }

        return $this->json->create(UserResponse::toArray($user));
    }
}
