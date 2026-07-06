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
 * PUT /organizations/{organization_id} — an admin updates the settings of their
 * own organization (`name`, AI, notification, webhook); `slug`, `custom_domain`,
 * and `is_active` are applied only for a superadmin, who may target any org.
 */
final readonly class UpdateOrganizationHandler implements RequestHandlerInterface
{
    public function __construct(
        private UpdateOrganizationUseCaseInterface $useCase,
        private JsonResponseFactory $json,
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $ownOrgId = AuthContext::organizationId($request);
        $actorId = AuthContext::userId($request);
        $role = AuthContext::role($request);

        if ($role === null) {
            return $this->problemDetails->create($request, 'unauthorized', 'Unauthorized', 401, 'Authentication required.');
        }

        if (!$role->canManageOrganization()) {
            return $this->problemDetails->create($request, 'forbidden', 'Forbidden', 403, 'Organization management is required.');
        }

        $params = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $organizationId = is_array($params) && is_string($params['organization_id'] ?? null) ? $params['organization_id'] : '';

        $isSuperadmin = $role === Role::Superadmin;

        if (!$isSuperadmin && $organizationId !== $ownOrgId) {
            return $this->problemDetails->create($request, 'forbidden', 'Forbidden', 403, 'You may only update your own organization.');
        }

        /** @var array<string, mixed> $body */
        $body = (array) json_decode((string) $request->getBody(), true);
        $fields = UpdateOrganizationRequest::parse($body);

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

        $organization = $this->useCase->execute(new UpdateOrganizationInput(
            organizationId: $organizationId,
            actorId: $actorId,
            isSuperadmin: $isSuperadmin,
            name: $fields->name,
            aiSummaryEnabled: $fields->aiSummaryEnabled,
            notificationEmailProvided: $fields->notificationEmailProvided,
            notificationEmail: $fields->notificationEmail,
            webhookUrlProvided: $fields->webhookUrlProvided,
            webhookUrl: $fields->webhookUrl,
            slug: $fields->slug,
            customDomainProvided: $fields->customDomainProvided,
            customDomain: $fields->customDomain,
            isActive: $fields->isActive,
        ));

        return $this->json->create(OrganizationResponse::toArray($organization));
    }
}
