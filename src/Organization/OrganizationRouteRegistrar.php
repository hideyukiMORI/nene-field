<?php

declare(strict_types=1);

namespace NeneField\Organization;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Registers organization (tenant) routes. `GET`/`POST /organizations` are
 * superadmin provisioning (tenant-resolution-bypassed); the `{organization_id}`
 * routes are admin (own org) or superadmin (any). Invoked with the {@see Router}
 * during runtime assembly (see {@see \NeneField\Http\RuntimeServiceProvider}).
 */
final readonly class OrganizationRouteRegistrar
{
    public function __construct(
        private ListOrganizationsHandler $listHandler,
        private CreateOrganizationHandler $createHandler,
        private GetOrganizationHandler $getHandler,
        private UpdateOrganizationHandler $updateHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $router->get('/organizations', fn (ServerRequestInterface $r) => $this->listHandler->handle($r));
        $router->post('/organizations', fn (ServerRequestInterface $r) => $this->createHandler->handle($r));
        $router->get('/organizations/{organization_id}', fn (ServerRequestInterface $r) => $this->getHandler->handle($r));
        $router->put('/organizations/{organization_id}', fn (ServerRequestInterface $r) => $this->updateHandler->handle($r));
    }
}
