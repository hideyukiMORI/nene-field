<?php

declare(strict_types=1);

namespace NeneField\User;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\PaginationQueryParser;
use Nene2\Http\PaginationResponse;
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

        $pagination = PaginationQueryParser::parse($request, self::DEFAULT_LIMIT, self::MAX_LIMIT);
        $output = $this->useCase->execute($organizationId, $pagination->limit, $pagination->offset);

        return $this->json->create(
            (new PaginationResponse(
                items: array_map(static fn (User $u): array => UserResponse::toArray($u), $output->items),
                limit: $output->limit,
                offset: $output->offset,
                total: $output->total,
            ))->toArray(),
        );
    }
}
