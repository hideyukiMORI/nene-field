<?php

declare(strict_types=1);

namespace NeneField\Template;

interface CreateTemplateUseCaseInterface
{
    public function execute(CreateTemplateInput $input): ReportTemplate;
}
