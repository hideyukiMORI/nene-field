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
 * PUT /reports/{report_id} — update an own draft/rejected report (submitter only).
 */
final readonly class UpdateReportHandler implements RequestHandlerInterface
{
    public function __construct(
        private UpdateReportUseCaseInterface $useCase,
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

        /** @var array<string, mixed> $body */
        $body = (array) json_decode((string) $request->getBody(), true);
        $fields = ReportRequest::parse($body);

        if ($fields->errors !== []) {
            return $this->problemDetails->create(
                $request,
                'validation-failed',
                'Validation Failed',
                422,
                'The request body contains invalid values.',
                ['errors' => $fields->errors],
            );
        }

        $report = $this->useCase->execute(new UpdateReportInput(
            organizationId: $organizationId,
            actorId: $actorId,
            reportId: $reportId,
            title: $fields->title,
            body: $fields->body,
            workDate: $fields->workDate,
            tags: $fields->tags,
            templateId: $fields->templateId,
            projectCode: $fields->projectCode,
            invoiceWorkOrderId: $fields->invoiceWorkOrderId,
            recordsEntityId: $fields->recordsEntityId,
        ));

        return $this->json->create(ReportResponse::toArray($report));
    }
}
