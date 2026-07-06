<?php

declare(strict_types=1);

namespace NeneField\Report;

use Nene2\Error\DomainExceptionHandlerInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * Maps {@see ReportNotEditableException} to a 409 problem+json response,
 * replacing the previous submit/delete/update try/catch blocks. The detail is
 * carried on the exception so each origin (submit / delete / update) keeps its
 * own context-specific wording.
 */
final readonly class ReportNotEditableExceptionHandler implements DomainExceptionHandlerInterface
{
    public function __construct(
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function supports(Throwable $exception): bool
    {
        return $exception instanceof ReportNotEditableException;
    }

    public function handle(Throwable $exception, ServerRequestInterface $request): ResponseInterface
    {
        return $this->problemDetails->create($request, 'report-not-editable', 'Report Not Editable', 409, $exception->getMessage());
    }
}
