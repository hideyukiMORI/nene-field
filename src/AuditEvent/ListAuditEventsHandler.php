<?php

declare(strict_types=1);

namespace NeneField\AuditEvent;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\PaginationQueryParser;
use Nene2\Http\PaginationResponse;
use NeneField\Auth\AuthContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * GET /audit-events — list audit events in the caller's organization (admin
 * only). Envelope: `{ items, limit, offset, total }`.
 */
final readonly class ListAuditEventsHandler implements RequestHandlerInterface
{
    private const MAX_LIMIT = 100;
    private const DEFAULT_LIMIT = 20;

    public function __construct(
        private ListAuditEventsUseCaseInterface $useCase,
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
        $output = $this->useCase->execute(
            $organizationId,
            AuditEventListRequest::toFilter($request->getQueryParams(), $pagination->limit, $pagination->offset),
        );

        return $this->json->create(
            (new PaginationResponse(
                items: array_map(static fn (AuditEvent $e): array => AuditEventResponse::toArray($e), $output->items),
                limit: $output->limit,
                offset: $output->offset,
                total: $output->total,
            ))->toArray(),
        );
    }
}
