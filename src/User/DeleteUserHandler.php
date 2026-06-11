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
 * DELETE /users/{user_id} — remove a user from the caller's organization
 * (admin only). An admin cannot delete their own account.
 */
final readonly class DeleteUserHandler implements RequestHandlerInterface
{
    public function __construct(
        private DeleteUserUseCaseInterface $useCase,
        private JsonResponseFactory $json,
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $organizationId = AuthContext::organizationId($request);
        $actorId = AuthContext::userId($request);
        $role = AuthContext::role($request);

        if ($organizationId === null || $role === null) {
            return $this->problemDetails->create($request, 'unauthorized', 'Unauthorized', 401, 'Authentication required.');
        }

        if (!$role->canManageOrganization()) {
            return $this->problemDetails->create($request, 'forbidden', 'Forbidden', 403, 'Organization management is required.');
        }

        $params = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $userId = is_array($params) && is_string($params['user_id'] ?? null) ? $params['user_id'] : '';

        try {
            $this->useCase->execute($organizationId, $actorId, $userId);
        } catch (UserNotFoundException) {
            return $this->problemDetails->create($request, 'user-not-found', 'User Not Found', 404, 'The user was not found.');
        } catch (CannotDeleteSelfException) {
            return $this->problemDetails->create($request, 'cannot-delete-self', 'Cannot Delete Self', 409, 'You cannot delete your own account.');
        }

        return $this->json->createEmpty(204);
    }
}
