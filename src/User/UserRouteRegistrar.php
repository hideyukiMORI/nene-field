<?php

declare(strict_types=1);

namespace NeneField\User;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Registers user-management routes (all admin-only, scoped to the caller's
 * organization). Invoked with the {@see Router} during runtime assembly
 * (see {@see \NeneField\Http\RuntimeServiceProvider}).
 */
final readonly class UserRouteRegistrar
{
    public function __construct(
        private ListUsersHandler $listHandler,
        private CreateUserHandler $createHandler,
        private GetUserHandler $getHandler,
        private UpdateUserHandler $updateHandler,
        private DeleteUserHandler $deleteHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $router->get('/users', fn (ServerRequestInterface $r) => $this->listHandler->handle($r));
        $router->post('/users', fn (ServerRequestInterface $r) => $this->createHandler->handle($r));
        $router->get('/users/{user_id}', fn (ServerRequestInterface $r) => $this->getHandler->handle($r));
        $router->put('/users/{user_id}', fn (ServerRequestInterface $r) => $this->updateHandler->handle($r));
        $router->delete('/users/{user_id}', fn (ServerRequestInterface $r) => $this->deleteHandler->handle($r));
    }
}
