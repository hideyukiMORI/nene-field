<?php

declare(strict_types=1);

namespace NeneField\Organization;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use NeneField\Auth\AuthContext;
use NeneField\Auth\Role;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * POST /organizations — superadmin provisions a new tenant. Tenant resolution is
 * bypassed for this path (see OrgResolverMiddleware).
 */
final readonly class CreateOrganizationHandler implements RequestHandlerInterface
{
    public function __construct(
        private CreateOrganizationUseCaseInterface $useCase,
        private JsonResponseFactory $json,
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actorId = AuthContext::userId($request);
        $role = AuthContext::role($request);

        if ($role === null) {
            return $this->problemDetails->create($request, 'unauthorized', 'Unauthorized', 401, 'Authentication required.');
        }

        if ($role !== Role::Superadmin) {
            return $this->problemDetails->create($request, 'forbidden', 'Forbidden', 403, 'Superadmin privileges are required.');
        }

        /** @var array<string, mixed> $body */
        $body = (array) json_decode((string) $request->getBody(), true);
        $fields = CreateOrganizationRequest::parse($body);

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

        $organization = $this->useCase->execute(new CreateOrganizationInput(
            actorId: $actorId,
            name: $fields->name,
            slug: $fields->slug,
            customDomain: $fields->customDomain,
            isActive: $fields->isActive,
        ));

        return $this->json->create(OrganizationResponse::toArray($organization), 201);
    }
}
