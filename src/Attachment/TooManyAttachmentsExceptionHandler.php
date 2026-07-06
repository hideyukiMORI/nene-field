<?php

declare(strict_types=1);

namespace NeneField\Attachment;

use Nene2\Error\DomainExceptionHandlerInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * Maps {@see TooManyAttachmentsException} to a 413 problem+json response,
 * replacing the previous per-handler try/catch blocks.
 */
final readonly class TooManyAttachmentsExceptionHandler implements DomainExceptionHandlerInterface
{
    public function __construct(
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function supports(Throwable $exception): bool
    {
        return $exception instanceof TooManyAttachmentsException;
    }

    public function handle(Throwable $exception, ServerRequestInterface $request): ResponseInterface
    {
        return $this->problemDetails->create($request, 'payload-too-large', 'Payload Too Large', 413, 'The report already has the maximum number of attachments.');
    }
}
