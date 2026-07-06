<?php

declare(strict_types=1);

namespace NeneField\Attachment;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Routing\Router;
use NeneField\Auth\AuthContext;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * GET /reports/{report_id}/attachments/{attachment_id} — stream an attachment to
 * an authenticated caller who can see the report. The bytes are SHA-256 verified
 * before being served; the storage path is never exposed (NF7).
 */
final readonly class DownloadAttachmentHandler implements RequestHandlerInterface
{
    public function __construct(
        private DownloadAttachmentUseCaseInterface $useCase,
        private Psr17Factory $psr17,
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $organizationId = AuthContext::organizationId($request);
        $actorId = AuthContext::userId($request);
        $role = AuthContext::role($request);

        if ($organizationId === null || $actorId === null || $role === null) {
            return $this->problemDetails->create($request, 'unauthorized', 'Unauthorized', 401, 'Authentication required.');
        }

        $params = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $reportId = is_array($params) && is_string($params['report_id'] ?? null) ? $params['report_id'] : '';
        $attachmentId = is_array($params) && is_string($params['attachment_id'] ?? null) ? $params['attachment_id'] : '';

        $download = $this->useCase->execute($organizationId, $reportId, $attachmentId, $actorId, $role);

        $attachment = $download->attachment;

        return $this->psr17->createResponse(200)
            ->withHeader('Content-Type', $attachment->mimeType)
            ->withHeader('Content-Length', (string) $attachment->fileSize)
            ->withHeader('Content-Disposition', self::contentDisposition($attachment->filename))
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withBody($this->psr17->createStream($download->contents));
    }

    /**
     * RFC 6266 / 5987 — ASCII fallback plus a UTF-8 `filename*` for non-ASCII
     * names, served inline (images/PDF render in the browser).
     */
    private static function contentDisposition(string $filename): string
    {
        $ascii = preg_replace('/[^\x20-\x7E]/', '_', $filename) ?? 'attachment';
        $ascii = str_replace('"', '', $ascii);

        return sprintf("inline; filename=\"%s\"; filename*=UTF-8''%s", $ascii, rawurlencode($filename));
    }
}
