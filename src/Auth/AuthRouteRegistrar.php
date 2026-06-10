<?php

declare(strict_types=1);

namespace NeneField\Auth;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Registers authentication routes. Invoked with the {@see Router} during runtime
 * assembly (see {@see \NeneField\Http\RuntimeServiceProvider}).
 */
final readonly class AuthRouteRegistrar
{
    public function __construct(
        private LoginHandler $loginHandler,
        private GetCurrentUserHandler $getCurrentUserHandler,
        private LogoutHandler $logoutHandler,
        private ChangePasswordHandler $changePasswordHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $router->post('/auth/login', fn (ServerRequestInterface $request) => $this->loginHandler->handle($request));
        $router->get('/auth/me', fn (ServerRequestInterface $request) => $this->getCurrentUserHandler->handle($request));
        $router->post('/auth/logout', fn (ServerRequestInterface $request) => $this->logoutHandler->handle($request));
        $router->post('/auth/change-password', fn (ServerRequestInterface $request) => $this->changePasswordHandler->handle($request));
    }
}
