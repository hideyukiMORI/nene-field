<?php

declare(strict_types=1);

namespace NeneField\Auth;

use LogicException;
use Nene2\Auth\BearerTokenMiddleware;
use Nene2\Auth\GuardedJwtSecretResolver;
use Nene2\Auth\LocalBearerTokenVerifier;
use Nene2\Auth\TokenIssuerInterface;
use Nene2\Auth\TokenVerifierInterface;
use Nene2\Config\AppConfig;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\ClockInterface;
use Nene2\Http\JsonResponseFactory;
use NeneField\User\UserRepositoryInterface;
use Psr\Container\ContainerInterface;

/**
 * Wires authentication: the local JWT verifier/issuer, login / me / logout /
 * change-password use cases and handlers, the route registrar, the org-guard
 * middleware, and the bearer-token middleware (with public paths excluded).
 */
final readonly class AuthServiceProvider implements ServiceProviderInterface
{
    /**
     * Development-only fallback secret, injected into
     * {@see GuardedJwtSecretResolver} as the product's development secret. It is
     * used **only** off-production, and only when the operator opts in via
     * `NENE2_ALLOW_DEV_SECRET=1` and `NENE2_LOCAL_JWT_SECRET` is unset. This
     * constant is public in the OSS repository, so signing real tokens with it
     * would be a full auth bypass — production always fails closed instead.
     */
    private const DEFAULT_DEV_SECRET = 'nene-field-dev-secret';

    /** Public paths that do not require a bearer token. */
    private const PUBLIC_PATHS = ['/health', '/machine/health', '/auth/login'];

    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                LocalBearerTokenVerifier::class,
                static function (ContainerInterface $container): LocalBearerTokenVerifier {
                    $config = $container->get(AppConfig::class);

                    if (!$config instanceof AppConfig) {
                        throw new LogicException('Application config service is invalid.');
                    }

                    return new LocalBearerTokenVerifier(
                        GuardedJwtSecretResolver::fromConfig($config, self::DEFAULT_DEV_SECRET),
                    );
                },
            )
            ->set(
                TokenIssuerInterface::class,
                static function (ContainerInterface $container): TokenIssuerInterface {
                    $verifier = $container->get(LocalBearerTokenVerifier::class);

                    if (!$verifier instanceof TokenIssuerInterface) {
                        throw new LogicException('Token issuer service is invalid.');
                    }

                    return $verifier;
                },
            )
            ->set(
                TokenVerifierInterface::class,
                static function (ContainerInterface $container): TokenVerifierInterface {
                    $verifier = $container->get(LocalBearerTokenVerifier::class);

                    if (!$verifier instanceof TokenVerifierInterface) {
                        throw new LogicException('Token verifier service is invalid.');
                    }

                    return $verifier;
                },
            )
            ->set(
                LoginUseCaseInterface::class,
                static function (ContainerInterface $container): LoginUseCaseInterface {
                    return new LoginUseCase(
                        self::users($container),
                        self::tokenIssuer($container),
                        self::clock($container),
                    );
                },
            )
            ->set(
                GetCurrentUserUseCaseInterface::class,
                static fn (ContainerInterface $container): GetCurrentUserUseCaseInterface
                    => new GetCurrentUserUseCase(self::users($container)),
            )
            ->set(
                ChangePasswordUseCaseInterface::class,
                static fn (ContainerInterface $container): ChangePasswordUseCaseInterface
                    => new ChangePasswordUseCase(self::users($container), self::clock($container)),
            )
            ->set(
                LoginHandler::class,
                static function (ContainerInterface $container): LoginHandler {
                    $useCase = $container->get(LoginUseCaseInterface::class);

                    if (!$useCase instanceof LoginUseCaseInterface) {
                        throw new LogicException('Login use case service is invalid.');
                    }

                    return new LoginHandler($useCase, self::json($container), self::problemDetails($container));
                },
            )
            ->set(
                GetCurrentUserHandler::class,
                static function (ContainerInterface $container): GetCurrentUserHandler {
                    $useCase = $container->get(GetCurrentUserUseCaseInterface::class);

                    if (!$useCase instanceof GetCurrentUserUseCaseInterface) {
                        throw new LogicException('Get current user use case service is invalid.');
                    }

                    return new GetCurrentUserHandler($useCase, self::json($container), self::problemDetails($container));
                },
            )
            ->set(
                ChangePasswordHandler::class,
                static function (ContainerInterface $container): ChangePasswordHandler {
                    $useCase = $container->get(ChangePasswordUseCaseInterface::class);

                    if (!$useCase instanceof ChangePasswordUseCaseInterface) {
                        throw new LogicException('Change password use case service is invalid.');
                    }

                    return new ChangePasswordHandler($useCase, self::json($container), self::problemDetails($container));
                },
            )
            ->set(
                LogoutHandler::class,
                static fn (ContainerInterface $container): LogoutHandler => new LogoutHandler(self::json($container)),
            )
            ->set(
                AuthRouteRegistrar::class,
                static function (ContainerInterface $container): AuthRouteRegistrar {
                    $login = $container->get(LoginHandler::class);
                    $me = $container->get(GetCurrentUserHandler::class);
                    $logout = $container->get(LogoutHandler::class);
                    $changePassword = $container->get(ChangePasswordHandler::class);

                    if (
                        !$login instanceof LoginHandler
                        || !$me instanceof GetCurrentUserHandler
                        || !$logout instanceof LogoutHandler
                        || !$changePassword instanceof ChangePasswordHandler
                    ) {
                        throw new LogicException('Auth handler services are invalid.');
                    }

                    return new AuthRouteRegistrar($login, $me, $logout, $changePassword);
                },
            )
            ->set(
                OrgGuardMiddleware::class,
                static fn (ContainerInterface $container): OrgGuardMiddleware
                    => new OrgGuardMiddleware(self::problemDetails($container)),
            )
            ->set(
                BearerTokenMiddleware::class,
                static function (ContainerInterface $container): BearerTokenMiddleware {
                    $verifier = $container->get(TokenVerifierInterface::class);

                    if (!$verifier instanceof TokenVerifierInterface) {
                        throw new LogicException('Token verifier service is invalid.');
                    }

                    return new BearerTokenMiddleware(
                        self::problemDetails($container),
                        $verifier,
                        excludedPaths: self::PUBLIC_PATHS,
                    );
                },
            );
    }

    private static function users(ContainerInterface $container): UserRepositoryInterface
    {
        $users = $container->get(UserRepositoryInterface::class);

        if (!$users instanceof UserRepositoryInterface) {
            throw new LogicException('User repository service is invalid.');
        }

        return $users;
    }

    private static function tokenIssuer(ContainerInterface $container): TokenIssuerInterface
    {
        $issuer = $container->get(TokenIssuerInterface::class);

        if (!$issuer instanceof TokenIssuerInterface) {
            throw new LogicException('Token issuer service is invalid.');
        }

        return $issuer;
    }

    private static function clock(ContainerInterface $container): ClockInterface
    {
        $clock = $container->get(ClockInterface::class);

        if (!$clock instanceof ClockInterface) {
            throw new LogicException('Clock service is invalid.');
        }

        return $clock;
    }

    private static function json(ContainerInterface $container): JsonResponseFactory
    {
        $json = $container->get(JsonResponseFactory::class);

        if (!$json instanceof JsonResponseFactory) {
            throw new LogicException('JSON response factory service is invalid.');
        }

        return $json;
    }

    private static function problemDetails(ContainerInterface $container): ProblemDetailsResponseFactory
    {
        $problemDetails = $container->get(ProblemDetailsResponseFactory::class);

        if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
            throw new LogicException('Problem details response factory service is invalid.');
        }

        return $problemDetails;
    }
}
