<?php

declare(strict_types=1);

namespace NeneField\Report;

use Nene2\Error\DomainExceptionHandlerInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * Maps {@see ReportNotInSubmittedStateException} to a 409 problem+json response,
 * replacing the previous approve/reject try/catch blocks.
 */
final readonly class ReportNotInSubmittedStateExceptionHandler implements DomainExceptionHandlerInterface
{
    public function __construct(
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function supports(Throwable $exception): bool
    {
        return $exception instanceof ReportNotInSubmittedStateException;
    }

    public function handle(Throwable $exception, ServerRequestInterface $request): ResponseInterface
    {
        return $this->problemDetails->create($request, 'report-not-in-submitted-state', 'Report Not In Submitted State', 409, 'The report is not awaiting approval.');
    }
}
