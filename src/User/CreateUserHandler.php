<?php

declare(strict_types=1);

namespace NeneField\User;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use NeneField\Auth\AuthContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * POST /users — create a user in the caller's organization (admin only). The
 * organization comes from the authenticated token, never from request input, so
 * a user cannot be created in another tenant.
 */
final readonly class CreateUserHandler implements RequestHandlerInterface
{
    public function __construct(
        private CreateUserUseCaseInterface $useCase,
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

        /** @var array<string, mixed> $body */
        $body = (array) json_decode((string) $request->getBody(), true);
        $fields = CreateUserRequest::parse($body);

        if ($fields->errors !== [] || $fields->role === null) {
            return $this->problemDetails->create(
                $request,
                'validation-failed',
                'Validation Failed',
                422,
                'The request body contains invalid values.',
                ['errors' => $fields->errors],
            );
        }

        $user = $this->useCase->execute(new CreateUserInput(
            organizationId: $organizationId,
            actorId: $actorId,
            name: $fields->name,
            email: $fields->email,
            role: $fields->role,
            password: $fields->password,
        ));

        return $this->json->create(UserResponse::toArray($user), 201);
    }
}
