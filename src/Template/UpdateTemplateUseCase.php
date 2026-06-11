<?php

declare(strict_types=1);

namespace NeneField\Template;

use Closure;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\Http\ClockInterface;
use NeneField\AuditEvent\AuditRecorderInterface;

final readonly class UpdateTemplateUseCase implements UpdateTemplateUseCaseInterface
{
    /**
     * @param Closure(DatabaseQueryExecutorInterface): AuditRecorderInterface $auditFactory
     */
    public function __construct(
        private TemplateRepositoryInterface $templates,
        private DatabaseTransactionManagerInterface $tx,
        private Closure $auditFactory,
        private ClockInterface $clock,
    ) {
    }

    public function execute(UpdateTemplateInput $input): ReportTemplate
    {
        $existing = $this->templates->findById($input->organizationId, $input->templateId);

        if ($existing === null) {
            throw new TemplateNotFoundException();
        }

        $now = $this->clock->now()->format('Y-m-d H:i:s');

        $updated = new ReportTemplate(
            templateId: $existing->templateId,
            organizationId: $existing->organizationId,
            name: $input->name ?? $existing->name,
            description: $input->descriptionProvided ? $input->description : $existing->description,
            fields: $input->fields ?? $existing->fields,
            isDefault: $input->isDefault ?? $existing->isDefault,
            createdAt: $existing->createdAt,
            updatedAt: $now,
        );

        $this->tx->transactional(function (DatabaseQueryExecutorInterface $exec) use ($existing, $updated, $input, $now): void {
            if ($updated->isDefault) {
                $this->templates->clearDefault($exec, $input->organizationId, $updated->templateId, $now);
            }

            $this->templates->update($exec, $updated);
            ($this->auditFactory)($exec)->record(
                $input->actorId,
                $input->organizationId,
                'template.updated',
                'ReportTemplate',
                $updated->templateId,
                TemplateResponse::toArray($existing),
                TemplateResponse::toArray($updated),
            );
        });

        return $updated;
    }
}
