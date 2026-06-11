<?php

declare(strict_types=1);

namespace NeneField\Template;

use Closure;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\Http\ClockInterface;
use NeneField\AuditEvent\AuditRecorderInterface;
use NeneField\Support\Uuid;

final readonly class CreateTemplateUseCase implements CreateTemplateUseCaseInterface
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

    public function execute(CreateTemplateInput $input): ReportTemplate
    {
        $now = $this->clock->now()->format('Y-m-d H:i:s');

        $template = new ReportTemplate(
            templateId: Uuid::v4(),
            organizationId: $input->organizationId,
            name: $input->name,
            description: $input->description,
            fields: $input->fields,
            isDefault: $input->isDefault,
            createdAt: $now,
            updatedAt: $now,
        );

        $this->tx->transactional(function (DatabaseQueryExecutorInterface $exec) use ($template, $input, $now): void {
            if ($template->isDefault) {
                $this->templates->clearDefault($exec, $input->organizationId, $template->templateId, $now);
            }

            $this->templates->insert($exec, $template);
            ($this->auditFactory)($exec)->record(
                $input->actorId,
                $input->organizationId,
                'template.created',
                'ReportTemplate',
                $template->templateId,
                null,
                TemplateResponse::toArray($template),
            );
        });

        return $template;
    }
}
