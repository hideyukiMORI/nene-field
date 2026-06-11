<?php

declare(strict_types=1);

namespace NeneField\Organization;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use NeneField\Auth\AuthContext;
use NeneField\Auth\Role;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * GET /organizations/{organization_id} — an admin may read only their own
 * organization; a superadmin may read any.
 */
final readonly class GetOrganizationHandler implements RequestHandlerInterface
{
    public function __construct(
        private GetOrganizationUseCaseInterface $useCase,
        private JsonResponseFactory $json,
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $ownOrgId = AuthContext::organizationId($request);
        $role = AuthContext::role($request);

        if ($role === null) {
            return $this->problemDetails->create($request, 'unauthorized', 'Unauthorized', 401, 'Authentication required.');
        }

        if (!$role->canManageOrganization()) {
            return $this->problemDetails->create($request, 'forbidden', 'Forbidden', 403, 'Organization management is required.');
        }

        $params = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $organizationId = is_array($params) && is_string($params['organization_id'] ?? null) ? $params['organization_id'] : '';

        if ($role !== Role::Superadmin && $organizationId !== $ownOrgId) {
            return $this->problemDetails->create($request, 'forbidden', 'Forbidden', 403, 'You may only access your own organization.');
        }

        $organization = $this->useCase->execute($organizationId);

        if ($organization === null) {
            return $this->problemDetails->create($request, 'organization-not-found', 'Organization Not Found', 404, 'The organization was not found.');
        }

        return $this->json->create(OrganizationResponse::toArray($organization));
    }
}
