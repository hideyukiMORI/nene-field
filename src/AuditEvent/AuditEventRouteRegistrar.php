<?php

declare(strict_types=1);

namespace NeneField\AuditEvent;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Registers audit-trail read routes (admin only, enforced in the handlers).
 * Invoked with the {@see Router} during runtime assembly.
 */
final readonly class AuditEventRouteRegistrar
{
    public function __construct(
        private ListAuditEventsHandler $listHandler,
        private ExportAuditEventsCsvHandler $exportHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $router->get('/audit-events', fn (ServerRequestInterface $r) => $this->listHandler->handle($r));
        $router->get('/audit-events/export', fn (ServerRequestInterface $r) => $this->exportHandler->handle($r));
    }
}
