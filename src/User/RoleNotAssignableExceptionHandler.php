<?php

declare(strict_types=1);

namespace NeneField\User;

use Nene2\Error\DomainExceptionHandlerInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * Maps {@see RoleNotAssignableException} to a 422 validation-failed response,
 * replacing the create/update-user try/catch blocks. The rejected role is
 * reported against the `role` field, preserving the original responses.
 */
final readonly class RoleNotAssignableExceptionHandler implements DomainExceptionHandlerInterface
{
    public function __construct(
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function supports(Throwable $exception): bool
    {
        return $exception instanceof RoleNotAssignableException;
    }

    public function handle(Throwable $exception, ServerRequestInterface $request): ResponseInterface
    {
        return $this->problemDetails->create(
            $request,
            'validation-failed',
            'Validation Failed',
            422,
            $exception->getMessage(),
            ['errors' => [['field' => 'role', 'message' => $exception->getMessage(), 'code' => 'invalid_value']]],
        );
    }
}
