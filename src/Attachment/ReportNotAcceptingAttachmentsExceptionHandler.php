<?php

declare(strict_types=1);

namespace NeneField\Attachment;

use Nene2\Error\DomainExceptionHandlerInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * Maps {@see ReportNotAcceptingAttachmentsException} to a 409 problem+json
 * response, replacing the previous per-handler try/catch blocks.
 */
final readonly class ReportNotAcceptingAttachmentsExceptionHandler implements DomainExceptionHandlerInterface
{
    public function __construct(
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function supports(Throwable $exception): bool
    {
        return $exception instanceof ReportNotAcceptingAttachmentsException;
    }

    public function handle(Throwable $exception, ServerRequestInterface $request): ResponseInterface
    {
        return $this->problemDetails->create($request, 'report-not-accepting-attachments', 'Report Not Accepting Attachments', 409, 'Attachments can only be changed while the report is a draft or rejected.');
    }
}
