<?php

declare(strict_types=1);

namespace NeneField\Auth;

use Nene2\Error\DomainExceptionHandlerInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * Maps {@see InvalidCredentialsException} to a 401 problem+json response,
 * replacing the login / change-password try/catch blocks. The exception message
 * is deliberately generic so account existence/status is not disclosed.
 */
final readonly class InvalidCredentialsExceptionHandler implements DomainExceptionHandlerInterface
{
    public function __construct(
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function supports(Throwable $exception): bool
    {
        return $exception instanceof InvalidCredentialsException;
    }

    public function handle(Throwable $exception, ServerRequestInterface $request): ResponseInterface
    {
        // Static detail rather than $exception->getMessage(): the 401 wording
        // must not drift silently if the exception message changes, and the
        // message is deliberately generic (no account existence/status leak).
        return $this->problemDetails->create($request, 'unauthorized', 'Unauthorized', 401, 'The email or password is incorrect.');
    }
}
