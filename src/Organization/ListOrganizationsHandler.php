<?php

declare(strict_types=1);

namespace NeneField\Organization;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\PaginationQueryParser;
use Nene2\Http\PaginationResponse;
use NeneField\Auth\AuthContext;
use NeneField\Auth\Role;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * GET /organizations — superadmin lists all tenants (paginated). Tenant
 * resolution is bypassed for this path (see OrgResolverMiddleware).
 */
final readonly class ListOrganizationsHandler implements RequestHandlerInterface
{
    private const MAX_LIMIT = 100;
    private const DEFAULT_LIMIT = 20;

    public function __construct(
        private ListOrganizationsUseCaseInterface $useCase,
        private JsonResponseFactory $json,
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $role = AuthContext::role($request);

        if ($role === null) {
            return $this->problemDetails->create($request, 'unauthorized', 'Unauthorized', 401, 'Authentication required.');
        }

        if ($role !== Role::Superadmin) {
            return $this->problemDetails->create($request, 'forbidden', 'Forbidden', 403, 'Superadmin privileges are required.');
        }

        $pagination = PaginationQueryParser::parse($request, self::DEFAULT_LIMIT, self::MAX_LIMIT);
        $output = $this->useCase->execute($pagination->limit, $pagination->offset);

        return $this->json->create(
            (new PaginationResponse(
                items: array_map(static fn (Organization $o): array => OrganizationResponse::toArray($o), $output->items),
                limit: $output->limit,
                offset: $output->offset,
                total: $output->total,
            ))->toArray(),
        );
    }
}
