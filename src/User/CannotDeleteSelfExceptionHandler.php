<?php

declare(strict_types=1);

namespace NeneField\User;

use Nene2\Error\DomainExceptionHandlerInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * Maps {@see CannotDeleteSelfException} to a 409 problem+json response,
 * replacing the previous delete-user try/catch block.
 */
final readonly class CannotDeleteSelfExceptionHandler implements DomainExceptionHandlerInterface
{
    public function __construct(
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function supports(Throwable $exception): bool
    {
        return $exception instanceof CannotDeleteSelfException;
    }

    public function handle(Throwable $exception, ServerRequestInterface $request): ResponseInterface
    {
        return $this->problemDetails->create($request, 'cannot-delete-self', 'Cannot Delete Self', 409, 'You cannot delete your own account.');
    }
}
