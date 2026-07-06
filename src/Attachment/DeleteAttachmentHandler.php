<?php

declare(strict_types=1);

namespace NeneField\Attachment;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use NeneField\Auth\AuthContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * DELETE /reports/{report_id}/attachments/{attachment_id} — remove an attachment
 * from an own draft/rejected report (submitter only).
 */
final readonly class DeleteAttachmentHandler implements RequestHandlerInterface
{
    public function __construct(
        private DeleteAttachmentUseCaseInterface $useCase,
        private JsonResponseFactory $json,
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $organizationId = AuthContext::organizationId($request);
        $actorId = AuthContext::userId($request);

        if ($organizationId === null || $actorId === null) {
            return $this->problemDetails->create($request, 'unauthorized', 'Unauthorized', 401, 'Authentication required.');
        }

        $params = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $reportId = is_array($params) && is_string($params['report_id'] ?? null) ? $params['report_id'] : '';
        $attachmentId = is_array($params) && is_string($params['attachment_id'] ?? null) ? $params['attachment_id'] : '';

        $this->useCase->execute($organizationId, $actorId, $reportId, $attachmentId);

        return $this->json->createEmpty(204);
    }
}
