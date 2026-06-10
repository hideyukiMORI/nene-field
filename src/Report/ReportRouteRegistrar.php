<?php

declare(strict_types=1);

namespace NeneField\Report;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Registers report routes. Invoked with the {@see Router} during runtime assembly
 * (see {@see \NeneField\Http\RuntimeServiceProvider}).
 */
final readonly class ReportRouteRegistrar
{
    public function __construct(
        private CreateReportHandler $createHandler,
        private GetReportHandler $getHandler,
        private UpdateReportHandler $updateHandler,
        private DeleteReportHandler $deleteHandler,
        private SubmitReportHandler $submitHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $router->post('/reports', fn (ServerRequestInterface $r) => $this->createHandler->handle($r));
        $router->get('/reports/{report_id}', fn (ServerRequestInterface $r) => $this->getHandler->handle($r));
        $router->put('/reports/{report_id}', fn (ServerRequestInterface $r) => $this->updateHandler->handle($r));
        $router->delete('/reports/{report_id}', fn (ServerRequestInterface $r) => $this->deleteHandler->handle($r));
        $router->post('/reports/{report_id}/submit', fn (ServerRequestInterface $r) => $this->submitHandler->handle($r));
    }
}
