<?php

declare(strict_types=1);

namespace NeneField\User;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use NeneField\Auth\AuthContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * GET /users — list users in the caller's organization (admin only). Envelope:
 * `{ items, limit, offset, total }`.
 */
final readonly class ListUsersHandler implements RequestHandlerInterface
{
    private const MAX_LIMIT = 100;
    private const DEFAULT_LIMIT = 20;

    public function __construct(
        private ListUsersUseCaseInterface $useCase,
        private JsonResponseFactory $json,
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $organizationId = AuthContext::organizationId($request);
        $role = AuthContext::role($request);

        if ($organizationId === null || $role === null) {
            return $this->problemDetails->create($request, 'unauthorized', 'Unauthorized', 401, 'Authentication required.');
        }

        if (!$role->canManageOrganization()) {
            return $this->problemDetails->create($request, 'forbidden', 'Forbidden', 403, 'Organization management is required.');
        }

        $query = $request->getQueryParams();
        $output = $this->useCase->execute(
            $organizationId,
            self::intParam($query['limit'] ?? null, self::DEFAULT_LIMIT, 1, self::MAX_LIMIT),
            self::intParam($query['offset'] ?? null, 0, 0, PHP_INT_MAX),
        );

        return $this->json->create([
            'items' => array_map(static fn (User $u): array => UserResponse::toArray($u), $output->items),
            'limit' => $output->limit,
            'offset' => $output->offset,
            'total' => $output->total,
        ]);
    }

    private static function intParam(mixed $raw, int $default, int $min, int $max): int
    {
        if (!is_string($raw) && !is_int($raw)) {
            return $default;
        }

        return max($min, min($max, (int) $raw));
    }
}
