<?php

declare(strict_types=1);

namespace NeneField\Attachment;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use NeneField\Auth\AuthContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * POST /reports/{report_id}/attachments — upload a file to an own draft/rejected
 * report (multipart `file`). The media type is detected from the bytes by the
 * use case, not trusted from the client.
 */
final readonly class UploadAttachmentHandler implements RequestHandlerInterface
{
    public function __construct(
        private UploadAttachmentUseCaseInterface $useCase,
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

        $file = $request->getUploadedFiles()['file'] ?? null;

        if (!$file instanceof UploadedFileInterface) {
            return $this->validationFailed($request, 'file', 'file is required.', 'required');
        }

        if ($file->getError() === UPLOAD_ERR_INI_SIZE || $file->getError() === UPLOAD_ERR_FORM_SIZE) {
            return $this->problemDetails->create($request, 'payload-too-large', 'Payload Too Large', 413, 'The attachment exceeds the maximum file size.');
        }

        if ($file->getError() !== UPLOAD_ERR_OK) {
            return $this->validationFailed($request, 'file', 'The file upload failed.', 'invalid');
        }

        $contents = (string) $file->getStream();
        $filename = is_string($file->getClientFilename()) ? $file->getClientFilename() : 'attachment';

        $attachment = $this->useCase->execute(new UploadAttachmentInput(
            organizationId: $organizationId,
            actorId: $actorId,
            reportId: $reportId,
            filename: $filename,
            contents: $contents,
        ));

        return $this->json->create(AttachmentSummaryResponse::toArray($attachment), 201);
    }

    private function validationFailed(ServerRequestInterface $request, string $field, string $message, string $code): ResponseInterface
    {
        return $this->problemDetails->create(
            $request,
            'validation-failed',
            'Validation Failed',
            422,
            $message,
            ['errors' => [['field' => $field, 'message' => $message, 'code' => $code]]],
        );
    }
}
