<?php

declare(strict_types=1);

namespace NeneField\Template;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use NeneField\Auth\AuthContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * POST /templates — create a template in the caller's organization (admin only).
 */
final readonly class CreateTemplateHandler implements RequestHandlerInterface
{
    public function __construct(
        private CreateTemplateUseCaseInterface $useCase,
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
        $fields = CreateTemplateRequest::parse($body);

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

        $template = $this->useCase->execute(new CreateTemplateInput(
            organizationId: $organizationId,
            actorId: $actorId,
            name: $fields->name,
            description: $fields->description,
            fields: $fields->fields,
            isDefault: $fields->isDefault,
        ));

        return $this->json->create(TemplateResponse::toArray($template), 201);
    }
}
