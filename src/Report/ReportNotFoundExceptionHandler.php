<?php

declare(strict_types=1);

namespace NeneField\Report;

use Nene2\Error\DomainExceptionHandlerInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * Maps {@see ReportNotFoundException} to a 404 problem+json response, replacing
 * the previous per-handler try/catch blocks (approve/reject/submit/delete/update).
 */
final readonly class ReportNotFoundExceptionHandler implements DomainExceptionHandlerInterface
{
    public function __construct(
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function supports(Throwable $exception): bool
    {
        return $exception instanceof ReportNotFoundException;
    }

    public function handle(Throwable $exception, ServerRequestInterface $request): ResponseInterface
    {
        return $this->problemDetails->create($request, 'report-not-found', 'Report Not Found', 404, 'The report was not found.');
    }
}
