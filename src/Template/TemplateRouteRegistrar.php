<?php

declare(strict_types=1);

namespace NeneField\Template;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Registers report-template routes (list/get readable by any org member;
 * create/update/delete admin-only, enforced in the handlers). Invoked with the
 * {@see Router} during runtime assembly (see {@see \NeneField\Http\RuntimeServiceProvider}).
 */
final readonly class TemplateRouteRegistrar
{
    public function __construct(
        private ListTemplatesHandler $listHandler,
        private CreateTemplateHandler $createHandler,
        private GetTemplateHandler $getHandler,
        private UpdateTemplateHandler $updateHandler,
        private DeleteTemplateHandler $deleteHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $router->get('/templates', fn (ServerRequestInterface $r) => $this->listHandler->handle($r));
        $router->post('/templates', fn (ServerRequestInterface $r) => $this->createHandler->handle($r));
        $router->get('/templates/{template_id}', fn (ServerRequestInterface $r) => $this->getHandler->handle($r));
        $router->put('/templates/{template_id}', fn (ServerRequestInterface $r) => $this->updateHandler->handle($r));
        $router->delete('/templates/{template_id}', fn (ServerRequestInterface $r) => $this->deleteHandler->handle($r));
    }
}
