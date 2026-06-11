<?php

declare(strict_types=1);

namespace NeneField\Template;

interface DeleteTemplateUseCaseInterface
{
    /**
     * @throws TemplateNotFoundException when the template is missing or in another org
     */
    public function execute(string $organizationId, ?string $actorId, string $templateId): void;
}
