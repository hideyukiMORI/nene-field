<?php

declare(strict_types=1);

namespace NeneField\Report;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\PaginationQueryParser;
use Nene2\Http\PaginationResponse;
use NeneField\Auth\AuthContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * GET /reports — list reports in the org. Submitters are scoped to their own
 * reports; approver/admin see all. Envelope: `{ items, limit, offset, total }`.
 */
final readonly class ListReportsHandler implements RequestHandlerInterface
{
    private const MAX_LIMIT = 100;
    private const DEFAULT_LIMIT = 20;

    public function __construct(
        private ListReportsUseCaseInterface $useCase,
        private JsonResponseFactory $json,
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $organizationId = AuthContext::organizationId($request);
        $actorId = AuthContext::userId($request);
        $role = AuthContext::role($request);

        if ($organizationId === null || $actorId === null || $role === null) {
            return $this->problemDetails->create($request, 'unauthorized', 'Unauthorized', 401, 'Authentication required.');
        }

        $pagination = PaginationQueryParser::parse($request, self::DEFAULT_LIMIT, self::MAX_LIMIT);
        $output = $this->useCase->execute(
            $organizationId,
            $actorId,
            $role,
            self::filterFrom($request->getQueryParams(), $pagination->limit, $pagination->offset),
        );

        return $this->json->create(
            (new PaginationResponse(
                items: array_map(
                    static fn (ReportSummary $s): array => ReportSummaryResponse::toArray($s),
                    $output->items,
                ),
                limit: $output->limit,
                offset: $output->offset,
                total: $output->total,
            ))->toArray(),
        );
    }

    /**
     * @param array<string, mixed> $query
     */
    private static function filterFrom(array $query, int $limit, int $offset): ReportFilter
    {
        $sort = is_string($query['sort'] ?? null) && in_array($query['sort'], ReportFilter::SORTS, true)
            ? $query['sort']
            : 'work_date_desc';

        return new ReportFilter(
            userId: self::str($query, 'user_id'),
            workDateFrom: self::str($query, 'work_date_from'),
            workDateTo: self::str($query, 'work_date_to'),
            statuses: self::statuses($query['status'] ?? null),
            projectCode: self::str($query, 'project_code'),
            sort: $sort,
            limit: $limit,
            offset: $offset,
        );
    }

    /**
     * @param array<string, mixed> $query
     */
    private static function str(array $query, string $key): ?string
    {
        $value = $query[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * @return list<ReportStatus>
     */
    private static function statuses(mixed $raw): array
    {
        $values = is_array($raw) ? $raw : (is_string($raw) && $raw !== '' ? [$raw] : []);
        $result = [];
        foreach ($values as $value) {
            if (is_string($value)) {
                $status = ReportStatus::tryFrom($value);
                if ($status !== null) {
                    $result[] = $status;
                }
            }
        }

        return $result;
    }
}
