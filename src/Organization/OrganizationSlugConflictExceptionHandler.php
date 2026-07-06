<?php

declare(strict_types=1);

namespace NeneField\Organization;

use Nene2\Error\DomainExceptionHandlerInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * Maps {@see OrganizationSlugConflictException} to a 409 problem+json response,
 * replacing the previous per-handler try/catch blocks.
 */
final readonly class OrganizationSlugConflictExceptionHandler implements DomainExceptionHandlerInterface
{
    public function __construct(
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function supports(Throwable $exception): bool
    {
        return $exception instanceof OrganizationSlugConflictException;
    }

    public function handle(Throwable $exception, ServerRequestInterface $request): ResponseInterface
    {
        return $this->problemDetails->create($request, 'organization-slug-conflict', 'Slug Already Used', 409, 'An organization with this slug or custom domain already exists.');
    }
}
