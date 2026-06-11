<?php

declare(strict_types=1);

namespace NeneField\Template;

final readonly class GetTemplateUseCase implements GetTemplateUseCaseInterface
{
    public function __construct(
        private TemplateRepositoryInterface $templates,
    ) {
    }

    public function execute(string $organizationId, string $templateId): ?ReportTemplate
    {
        return $this->templates->findById($organizationId, $templateId);
    }
}
