<?php

declare(strict_types=1);

namespace NeneField\Auth;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * POST /auth/change-password — changes the authenticated user's own password.
 */
final readonly class ChangePasswordHandler implements RequestHandlerInterface
{
    private const MIN_PASSWORD_LENGTH = 8;

    public function __construct(
        private ChangePasswordUseCaseInterface $useCase,
        private JsonResponseFactory $json,
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $organizationId = AuthContext::organizationId($request);
        $userId = AuthContext::userId($request);

        if ($organizationId === null || $userId === null) {
            return $this->problemDetails->create($request, 'unauthorized', 'Unauthorized', 401, 'Authentication required.');
        }

        /** @var array<string, mixed> $body */
        $body = (array) json_decode((string) $request->getBody(), true);
        $current = is_string($body['current_password'] ?? null) ? $body['current_password'] : '';
        $new = is_string($body['new_password'] ?? null) ? $body['new_password'] : '';

        if (strlen($new) < self::MIN_PASSWORD_LENGTH) {
            return $this->problemDetails->create(
                $request,
                'validation-failed',
                'Validation Failed',
                422,
                'The request body contains invalid values.',
                ['errors' => [[
                    'field' => 'new_password',
                    'message' => 'new_password must be at least ' . self::MIN_PASSWORD_LENGTH . ' characters.',
                    'code' => 'too_short',
                ]]],
            );
        }

        $this->useCase->execute(new ChangePasswordInput($organizationId, $userId, $current, $new));

        return $this->json->createEmpty(204);
    }
}
