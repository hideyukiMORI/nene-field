<?php

declare(strict_types=1);

namespace NeneField\Attachment;

use Nene2\Error\DomainExceptionHandlerInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * Maps {@see AttachmentIntegrityException} to a 500 problem+json response,
 * replacing the previous per-handler try/catch block. The storage path is
 * never exposed (NF7).
 */
final readonly class AttachmentIntegrityExceptionHandler implements DomainExceptionHandlerInterface
{
    public function __construct(
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function supports(Throwable $exception): bool
    {
        return $exception instanceof AttachmentIntegrityException;
    }

    public function handle(Throwable $exception, ServerRequestInterface $request): ResponseInterface
    {
        return $this->problemDetails->create($request, 'attachment-integrity-failed', 'Attachment Integrity Check Failed', 500, 'The attachment could not be served.');
    }
}
