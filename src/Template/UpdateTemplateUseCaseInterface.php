<?php

declare(strict_types=1);

namespace NeneField\Template;

interface UpdateTemplateUseCaseInterface
{
    /**
     * @throws TemplateNotFoundException when the template is missing or in another org
     */
    public function execute(UpdateTemplateInput $input): ReportTemplate;
}
