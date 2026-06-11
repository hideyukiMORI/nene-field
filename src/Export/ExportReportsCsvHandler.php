<?php

declare(strict_types=1);

namespace NeneField\Export;

use Nene2\Error\ProblemDetailsResponseFactory;
use NeneField\Auth\AuthContext;
use NeneField\Report\ReportExportFilter;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * GET /export/csv — export reports as a UTF-8 (BOM) CSV file (admin only). The
 * export action is itself audited (`report.exported`, filters only).
 */
final readonly class ExportReportsCsvHandler implements RequestHandlerInterface
{
    public function __construct(
        private ExportReportsUseCaseInterface $useCase,
        private Psr17Factory $psr17,
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $organizationId = AuthContext::organizationId($request);
        $actorId = AuthContext::userId($request);
        $role = AuthContext::role($request);

        if ($organizationId === null || $role === null) {
            return $this->problemDetails->create($request, 'unauthorized', 'Unauthorized', 401, 'Authentication required.');
        }

        if (!$role->canManageOrganization()) {
            return $this->problemDetails->create($request, 'forbidden', 'Forbidden', 403, 'Organization management is required.');
        }

        $fields = ExportReportsRequest::parse($request->getQueryParams());

        if ($fields->errors !== []) {
            return $this->problemDetails->create(
                $request,
                'validation-failed',
                'Validation Failed',
                422,
                'The request query contains invalid values.',
                ['errors' => $fields->errors],
            );
        }

        $export = $this->useCase->execute($organizationId, $actorId, new ReportExportFilter(
            workDateFrom: $fields->workDateFrom,
            workDateTo: $fields->workDateTo,
            statuses: $fields->statuses,
            userId: $fields->userId,
            projectCode: $fields->projectCode,
        ));

        $filename = sprintf('reports_%s_%s.csv', $fields->workDateFrom, $fields->workDateTo);

        return $this->psr17->createResponse(200)
            ->withHeader('Content-Type', 'text/csv; charset=utf-8')
            ->withHeader('Content-Disposition', sprintf('attachment; filename="%s"', $filename))
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withBody($this->psr17->createStream($export->csv));
    }
}
