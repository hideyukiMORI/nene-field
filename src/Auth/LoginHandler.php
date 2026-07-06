<?php

declare(strict_types=1);

namespace NeneField\Auth;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use NeneField\User\UserResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * POST /auth/login — tenant-scoped. The organization is resolved upstream
 * (OrgResolverMiddleware); this handler reads it from the request attribute.
 */
final readonly class LoginHandler implements RequestHandlerInterface
{
    private const ORG_ID_ATTRIBUTE = 'nene_field.org.id';

    public function __construct(
        private LoginUseCaseInterface $useCase,
        private JsonResponseFactory $json,
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $organizationId = $request->getAttribute(self::ORG_ID_ATTRIBUTE);

        if (!is_string($organizationId) || $organizationId === '') {
            return $this->problemDetails->create(
                $request,
                'unauthorized',
                'Unauthorized',
                401,
                'No organization context for this request.',
            );
        }

        /** @var array<string, mixed> $body */
        $body = (array) json_decode((string) $request->getBody(), true);
        $email = is_string($body['email'] ?? null) ? $body['email'] : '';
        $password = is_string($body['password'] ?? null) ? $body['password'] : '';

        $errors = [];
        if ($email === '') {
            $errors[] = ['field' => 'email', 'message' => 'email is required.', 'code' => 'required'];
        }
        if ($password === '') {
            $errors[] = ['field' => 'password', 'message' => 'password is required.', 'code' => 'required'];
        }
        if ($errors !== []) {
            return $this->problemDetails->create(
                $request,
                'validation-failed',
                'Validation Failed',
                422,
                'The request body contains invalid values.',
                ['errors' => $errors],
            );
        }

        $output = $this->useCase->execute(new LoginInput($organizationId, $email, $password));

        return $this->json->create([
            'token' => $output->token,
            'user' => UserResponse::toArray($output->user),
        ]);
    }
}
