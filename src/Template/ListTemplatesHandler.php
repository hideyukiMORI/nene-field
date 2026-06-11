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
 * GET /templates — list templates in the caller's organization. Readable by any
 * authenticated member (submitters need it to render the report form). Envelope:
 * `{ items }` (unpaginated, per OpenAPI).
 */
final readonly class ListTemplatesHandler implements RequestHandlerInterface
{
    public function __construct(
        private ListTemplatesUseCaseInterface $useCase,
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

        $output = $this->useCase->execute($organizationId);

        return $this->json->create([
            'items' => array_map(
                static fn (ReportTemplate $t): array => TemplateResponse::toArray($t),
                $output->items,
            ),
        ]);
    }
}
