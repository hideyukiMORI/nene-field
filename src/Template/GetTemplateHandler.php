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
 * GET /templates/{template_id} — fetch a template in the caller's organization.
 * Readable by any authenticated member.
 */
final readonly class GetTemplateHandler implements RequestHandlerInterface
{
    public function __construct(
        private GetTemplateUseCaseInterface $useCase,
        private JsonResponseFactory $json,
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $organizationId = AuthContext::organizationId($request);

        if ($organizationId === null) {
            return $this->problemDetails->create($request, 'unauthorized', 'Unauthorized', 401, 'Authentication required.');
        }

        $params = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $templateId = is_array($params) && is_string($params['template_id'] ?? null) ? $params['template_id'] : '';

        $template = $this->useCase->execute($organizationId, $templateId);

        if ($template === null) {
            return $this->problemDetails->create($request, 'template-not-found', 'Template Not Found', 404, 'The template was not found.');
        }

        return $this->json->create(TemplateResponse::toArray($template));
    }
}
