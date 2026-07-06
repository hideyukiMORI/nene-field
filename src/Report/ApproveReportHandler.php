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
 * POST /reports/{report_id}/approve — approver / admin only.
 */
final readonly class ApproveReportHandler implements RequestHandlerInterface
{
    public function __construct(
        private ApproveReportUseCaseInterface $useCase,
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

        if (!$role->canApprove()) {
            return $this->problemDetails->create($request, 'forbidden', 'Forbidden', 403, 'Only an approver or admin can approve reports.');
        }

        $params = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $reportId = is_array($params) && is_string($params['report_id'] ?? null) ? $params['report_id'] : '';

        /** @var array<string, mixed> $body */
        $body = (array) json_decode((string) $request->getBody(), true);
        $comment = is_string($body['comment'] ?? null) && $body['comment'] !== '' ? $body['comment'] : null;

        $report = $this->useCase->execute(new ApproveReportInput($organizationId, $actorId, $reportId, $comment));

        return $this->json->create(ReportResponse::toArray($report));
    }
}
