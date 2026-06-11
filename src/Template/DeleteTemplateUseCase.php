<?php

declare(strict_types=1);

namespace NeneField\Template;

use Closure;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use NeneField\AuditEvent\AuditRecorderInterface;

final readonly class DeleteTemplateUseCase implements DeleteTemplateUseCaseInterface
{
    /**
     * @param Closure(DatabaseQueryExecutorInterface): AuditRecorderInterface $auditFactory
     */
    public function __construct(
        private TemplateRepositoryInterface $templates,
        private DatabaseTransactionManagerInterface $tx,
        private Closure $auditFactory,
    ) {
    }

    public function execute(string $organizationId, ?string $actorId, string $templateId): void
    {
        $existing = $this->templates->findById($organizationId, $templateId);

        if ($existing === null) {
            throw new TemplateNotFoundException();
        }

        // Reports referencing this template keep their own snapshot; the template
        // row is removed without cascading (template_id becomes a historical id).
        $this->tx->transactional(function (DatabaseQueryExecutorInterface $exec) use ($existing, $organizationId, $actorId, $templateId): void {
            $this->templates->delete($exec, $organizationId, $templateId);
            ($this->auditFactory)($exec)->record(
                $actorId,
                $organizationId,
                'template.deleted',
                'ReportTemplate',
                $templateId,
                TemplateResponse::toArray($existing),
                null,
            );
        });
    }
}
