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

        try {
            $this->useCase->execute($organizationId, $actorId, $reportId, $attachmentId);
        } catch (AttachmentReportNotFoundException) {
            return $this->problemDetails->create($request, 'report-not-found', 'Report Not Found', 404, 'The report was not found.');
        } catch (ReportNotAcceptingAttachmentsException) {
            return $this->problemDetails->create($request, 'report-not-accepting-attachments', 'Report Not Accepting Attachments', 409, 'Attachments can only be changed while the report is a draft or rejected.');
        } catch (AttachmentNotFoundException) {
            return $this->problemDetails->create($request, 'attachment-not-found', 'Attachment Not Found', 404, 'The attachment was not found.');
        }

        return $this->json->createEmpty(204);
    }
}
