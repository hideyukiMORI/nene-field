<?php

declare(strict_types=1);

namespace NeneField\Report;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use NeneField\Auth\AuthContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * POST /reports/{report_id}/submit — submit an own draft/rejected report for approval.
 */
final readonly class SubmitReportHandler implements RequestHandlerInterface
{
    public function __construct(
        private SubmitReportUseCaseInterface $useCase,
        private JsonResponseFactory $json,
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $organizationId = AuthContext::organizationId($request);
        $actorId = AuthContext::userId($request);

        if ($organizationId === null || $actorId === null) {
            return $this->problemDetails->create($request, 'unauthorized', 'Unauthorized', 401, 'Authentication required.');
        }

        $params = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $reportId = is_array($params) && is_string($params['report_id'] ?? null) ? $params['report_id'] : '';

        try {
            $report = $this->useCase->execute($organizationId, $actorId, $reportId);
        } catch (ReportNotFoundException) {
            return $this->problemDetails->create($request, 'report-not-found', 'Report Not Found', 404, 'The report was not found.');
        } catch (ReportNotEditableException) {
            return $this->problemDetails->create($request, 'report-not-editable', 'Report Not Editable', 409, 'The report cannot be submitted in its current state.');
        }

        return $this->json->create(ReportResponse::toArray($report));
    }
}
