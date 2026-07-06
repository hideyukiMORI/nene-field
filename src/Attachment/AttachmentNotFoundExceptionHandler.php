<?php

declare(strict_types=1);

namespace NeneField\Attachment;

use Nene2\Error\DomainExceptionHandlerInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * Maps {@see AttachmentNotFoundException} to a 404 problem+json response,
 * replacing the previous per-handler try/catch blocks.
 */
final readonly class AttachmentNotFoundExceptionHandler implements DomainExceptionHandlerInterface
{
    public function __construct(
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function supports(Throwable $exception): bool
    {
        return $exception instanceof AttachmentNotFoundException;
    }

    public function handle(Throwable $exception, ServerRequestInterface $request): ResponseInterface
    {
        return $this->problemDetails->create($request, 'attachment-not-found', 'Attachment Not Found', 404, 'The attachment was not found.');
    }
}
