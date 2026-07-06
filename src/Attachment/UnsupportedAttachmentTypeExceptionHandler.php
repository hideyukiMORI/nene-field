<?php

declare(strict_types=1);

namespace NeneField\Attachment;

use Nene2\Error\DomainExceptionHandlerInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * Maps {@see UnsupportedAttachmentTypeException} to a 422 validation-failed
 * response, replacing the previous per-handler try/catch block. The detected
 * media type carried by the exception is surfaced in the detail and the
 * `errors` extension, preserving the original upload-handler response.
 */
final readonly class UnsupportedAttachmentTypeExceptionHandler implements DomainExceptionHandlerInterface
{
    public function __construct(
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function supports(Throwable $exception): bool
    {
        return $exception instanceof UnsupportedAttachmentTypeException;
    }

    public function handle(Throwable $exception, ServerRequestInterface $request): ResponseInterface
    {
        $detectedMimeType = $exception instanceof UnsupportedAttachmentTypeException ? $exception->detectedMimeType : '';
        $message = sprintf('Unsupported media type "%s". Allowed: image/jpeg, image/png, application/pdf.', $detectedMimeType);

        return $this->problemDetails->create(
            $request,
            'validation-failed',
            'Validation Failed',
            422,
            $message,
            ['errors' => [['field' => 'file', 'message' => $message, 'code' => 'unsupported_media_type']]],
        );
    }
}
