<?php

declare(strict_types=1);

namespace NeneField\User;

use Closure;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use NeneField\AuditEvent\AuditRecorderInterface;

final readonly class DeleteUserUseCase implements DeleteUserUseCaseInterface
{
    /**
     * @param Closure(DatabaseQueryExecutorInterface): AuditRecorderInterface $auditFactory
     */
    public function __construct(
        private UserRepositoryInterface $users,
        private DatabaseTransactionManagerInterface $tx,
        private Closure $auditFactory,
    ) {
    }

    public function execute(string $organizationId, ?string $actorId, string $userId): void
    {
        if ($actorId !== null && $actorId === $userId) {
            throw new CannotDeleteSelfException();
        }

        $existing = $this->users->findById($organizationId, $userId);

        if ($existing === null) {
            throw new UserNotFoundException();
        }

        $this->tx->transactional(function (DatabaseQueryExecutorInterface $exec) use ($existing, $organizationId, $actorId, $userId): void {
            $this->users->delete($exec, $organizationId, $userId);
            ($this->auditFactory)($exec)->record(
                $actorId,
                $organizationId,
                'user.deleted',
                'User',
                $userId,
                UserResponse::toArray($existing),
                null,
            );
        });
    }
}
