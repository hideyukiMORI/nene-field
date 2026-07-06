<?php

declare(strict_types=1);

namespace NeneField\Tests\Auth;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use NeneField\Auth\ChangePasswordHandler;
use NeneField\Auth\ChangePasswordInput;
use NeneField\Auth\ChangePasswordUseCaseInterface;
use NeneField\Auth\InvalidCredentialsException;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;

/**
 * Boundary handling for `POST /auth/change-password`. The new password must be at
 * least 8 characters; the boundary (7 rejected / 8 accepted) is enforced before
 * the use case runs.
 */
final class ChangePasswordHandlerTest extends TestCase
{
    public function test_new_password_one_under_min_is_rejected_without_calling_use_case(): void
    {
        $useCase = new SpyChangePasswordUseCase();
        $response = $this->dispatch($useCase, ['current_password' => 'oldpassw0rd', 'new_password' => str_repeat('a', 7)]);

        self::assertSame(422, $response->getStatusCode());
        self::assertFalse($useCase->called);
    }

    public function test_new_password_at_min_is_accepted(): void
    {
        $useCase = new SpyChangePasswordUseCase();
        $response = $this->dispatch($useCase, ['current_password' => 'oldpassw0rd', 'new_password' => str_repeat('a', 8)]);

        self::assertSame(204, $response->getStatusCode());
        self::assertTrue($useCase->called);
        self::assertSame(str_repeat('a', 8), $useCase->input?->newPassword);
    }

    public function test_missing_new_password_is_rejected(): void
    {
        $useCase = new SpyChangePasswordUseCase();
        $response = $this->dispatch($useCase, ['current_password' => 'oldpassw0rd']);

        self::assertSame(422, $response->getStatusCode());
        self::assertFalse($useCase->called);
    }

    public function test_wrong_current_password_propagates_to_error_middleware(): void
    {
        // The 401 mapping now lives in InvalidCredentialsExceptionHandler (wired
        // via the error middleware); the handler itself no longer catches it.
        $useCase = new SpyChangePasswordUseCase(throw: true);

        $this->expectException(InvalidCredentialsException::class);
        $this->dispatch($useCase, ['current_password' => 'wrong', 'new_password' => 'brandnewpass']);
    }

    public function test_unauthenticated_is_rejected(): void
    {
        $useCase = new SpyChangePasswordUseCase();
        $psr17 = new Psr17Factory();
        $handler = new ChangePasswordHandler($useCase, new JsonResponseFactory($psr17, $psr17), new ProblemDetailsResponseFactory($psr17, $psr17, 'https://nene-field.dev/problems/'));

        $request = $psr17->createServerRequest('POST', '/auth/change-password')
            ->withBody($psr17->createStream((string) json_encode(['current_password' => 'x', 'new_password' => 'longenough1'])));

        self::assertSame(401, $handler->handle($request)->getStatusCode());
        self::assertFalse($useCase->called);
    }

    /**
     * @param array<string, mixed> $body
     */
    private function dispatch(SpyChangePasswordUseCase $useCase, array $body): \Psr\Http\Message\ResponseInterface
    {
        $psr17 = new Psr17Factory();
        $handler = new ChangePasswordHandler(
            $useCase,
            new JsonResponseFactory($psr17, $psr17),
            new ProblemDetailsResponseFactory($psr17, $psr17, 'https://nene-field.dev/problems/'),
        );

        $request = $psr17->createServerRequest('POST', '/auth/change-password')
            ->withBody($psr17->createStream((string) json_encode($body)))
            ->withAttribute('nene2.auth.claims', ['sub' => 'user-1', 'role' => 'submitter', 'org' => 'org-1']);

        return $handler->handle($request);
    }
}

/**
 * Records whether the use case ran and the input it received; optionally rejects
 * the current password.
 */
final class SpyChangePasswordUseCase implements ChangePasswordUseCaseInterface
{
    public bool $called = false;
    public ?ChangePasswordInput $input = null;

    public function __construct(private readonly bool $throw = false)
    {
    }

    public function execute(ChangePasswordInput $input): void
    {
        $this->called = true;
        $this->input = $input;

        if ($this->throw) {
            throw new InvalidCredentialsException();
        }
    }
}
