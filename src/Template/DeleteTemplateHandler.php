<?php

declare(strict_types=1);

namespace NeneField\Template;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use NeneField\Auth\AuthContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * DELETE /templates/{template_id} — remove a template from the caller's
 * organization (admin only).
 */
final readonly class DeleteTemplateHandler implements RequestHandlerInterface
{
    public function __construct(
        private DeleteTemplateUseCaseInterface $useCase,
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
        $templateId = is_array($params) && is_string($params['template_id'] ?? null) ? $params['template_id'] : '';

        $this->useCase->execute($organizationId, $actorId, $templateId);

        return $this->json->createEmpty(204);
    }
}
