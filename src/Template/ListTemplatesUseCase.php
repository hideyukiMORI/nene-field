<?php

declare(strict_types=1);

namespace NeneField\Template;

final readonly class ListTemplatesUseCase implements ListTemplatesUseCaseInterface
{
    public function __construct(
        private TemplateRepositoryInterface $templates,
    ) {
    }

    public function execute(string $organizationId): ListTemplatesOutput
    {
        return new ListTemplatesOutput(items: $this->templates->listByOrg($organizationId));
    }
}
