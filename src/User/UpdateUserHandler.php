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
 * PUT /users/{user_id} — update a user's name, role, or active flag in the
 * caller's organization (admin only). Email is immutable.
 */
final readonly class UpdateUserHandler implements RequestHandlerInterface
{
    public function __construct(
        private UpdateUserUseCaseInterface $useCase,
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

        /** @var array<string, mixed> $body */
        $body = (array) json_decode((string) $request->getBody(), true);
        $fields = UpdateUserRequest::parse($body);

        if ($fields->errors !== []) {
            return $this->problemDetails->create(
                $request,
                'validation-failed',
                'Validation Failed',
                422,
                'The request body contains invalid values.',
                ['errors' => $fields->errors],
            );
        }

        try {
            $user = $this->useCase->execute(new UpdateUserInput(
                organizationId: $organizationId,
                actorId: $actorId,
                userId: $userId,
                name: $fields->name,
                role: $fields->role,
                isActive: $fields->isActive,
            ));
        } catch (UserNotFoundException) {
            return $this->problemDetails->create($request, 'user-not-found', 'User Not Found', 404, 'The user was not found.');
        } catch (RoleNotAssignableException $e) {
            return $this->problemDetails->create(
                $request,
                'validation-failed',
                'Validation Failed',
                422,
                $e->getMessage(),
                ['errors' => [['field' => 'role', 'message' => $e->getMessage(), 'code' => 'invalid_value']]],
            );
        }

        return $this->json->create(UserResponse::toArray($user));
    }
}
