<?php

declare(strict_types=1);

namespace NeneField\AuditEvent;

use Nene2\Error\ProblemDetailsResponseFactory;
use NeneField\Auth\AuthContext;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * GET /audit-events/export — export audit events as a UTF-8 (BOM) CSV (admin
 * only). The export action is itself audited (`audit.exported`).
 */
final readonly class ExportAuditEventsCsvHandler implements RequestHandlerInterface
{
    public function __construct(
        private ExportAuditEventsUseCaseInterface $useCase,
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

        $fields = AuditEventExportRequest::parse($request->getQueryParams());

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

        $export = $this->useCase->execute($organizationId, $actorId, new AuditEventExportFilter(
            occurredFrom: $fields->occurredFrom,
            occurredTo: $fields->occurredTo,
            entityType: $fields->entityType,
        ));

        return $this->psr17->createResponse(200)
            ->withHeader('Content-Type', 'text/csv; charset=utf-8')
            ->withHeader('Content-Disposition', 'attachment; filename="audit_events.csv"')
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withBody($this->psr17->createStream($export->csv));
    }
}
