<?php

declare(strict_types=1);

namespace NeneField\Export;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Registers data-export routes (admin only, enforced in the handler). Invoked
 * with the {@see Router} during runtime assembly.
 */
final readonly class ExportRouteRegistrar
{
    public function __construct(
        private ExportReportsCsvHandler $reportsCsvHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $router->get('/export/csv', fn (ServerRequestInterface $r) => $this->reportsCsvHandler->handle($r));
    }
}
