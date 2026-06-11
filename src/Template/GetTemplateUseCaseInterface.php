<?php

declare(strict_types=1);

namespace NeneField\Template;

interface GetTemplateUseCaseInterface
{
    public function execute(string $organizationId, string $templateId): ?ReportTemplate;
}
