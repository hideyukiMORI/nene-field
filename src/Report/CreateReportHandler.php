<?php

declare(strict_types=1);

namespace NeneField\Report;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use NeneField\Auth\AuthContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * POST /reports — create a draft report owned by the authenticated user.
 */
final readonly class CreateReportHandler implements RequestHandlerInterface
{
    public function __construct(
        private CreateReportUseCaseInterface $useCase,
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

        $report = $this->useCase->execute(new CreateReportInput(
            organizationId: $organizationId,
            actorId: $actorId,
            title: $fields->title,
            body: $fields->body,
            workDate: $fields->workDate,
            tags: $fields->tags,
            templateId: $fields->templateId,
            projectCode: $fields->projectCode,
            invoiceWorkOrderId: $fields->invoiceWorkOrderId,
            recordsEntityId: $fields->recordsEntityId,
        ));

        return $this->json->create(ReportResponse::toArray($report), 201);
    }
}
