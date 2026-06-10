<?php

declare(strict_types=1);

namespace NeneField\User;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Psr\Container\ContainerInterface;

final readonly class UserServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder->set(
            UserRepositoryInterface::class,
            static function (ContainerInterface $container): UserRepositoryInterface {
                $query = $container->get(DatabaseQueryExecutorInterface::class);

                if (!$query instanceof DatabaseQueryExecutorInterface) {
                    throw new LogicException('Database query executor service is invalid.');
                }

                return new PdoUserRepository($query);
            },
        );
    }
}
