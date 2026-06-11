<?php

declare(strict_types=1);

namespace NeneField\Template;

interface ListTemplatesUseCaseInterface
{
    public function execute(string $organizationId): ListTemplatesOutput;
}
